<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 业务场景错误演示控制器
 *
 * 每个方法模拟一种真实生产环境中常见的 Bug 类型，
 * 用于演示 AI DevOps Copilot 的自动分析和修复能力。
 */
class BusinessErrorController extends Controller
{
    // -------------------------------------------------------------------------
    // 场景 11: 订单金额计算错误（浮点精度 Bug）
    // 触发: 浮点数精度丢失导致金额对不上，抛出 RuntimeException
    // AI 修复方向: 改用 bcmath 或整数分做精确计算
    // -------------------------------------------------------------------------
    public function orderAmountPrecisionError(): mixed
    {
        $items = [
            ['price' => 0.1, 'qty' => 3],
            ['price' => 0.2, 'qty' => 2],
        ];

        $total = 0.0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['qty'];
        }

        // 0.1*3 + 0.2*2 在浮点计算中不等于 0.7
        $expected = 0.70;
        if ($total !== $expected) {
            throw new \RuntimeException(
                sprintf(
                    'Order amount mismatch: calculated=%.17f, expected=%.2f. ' .
                    'Floating-point precision error in price accumulation.',
                    $total,
                    $expected
                )
            );
        }

        return response()->json(['total' => $total]);
    }

    // -------------------------------------------------------------------------
    // 场景 12: SQL 注入风险（未使用参数绑定）
    // 触发: 用户输入直接拼接 SQL，触发 QueryException（非法字符）
    // AI 修复方向: 改用 DB::select() 参数绑定 / Eloquent where()
    // -------------------------------------------------------------------------
    public function sqlInjectionVulnerability(Request $request): mixed
    {
        $username = $request->query('username', "admin' OR '1'='1");

        // 危险写法：直接拼接用户输入到 SQL
        $sql = "SELECT * FROM users WHERE username = '" . $username . "' LIMIT 1";

        try {
            $result = DB::select($sql);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'SQL query failed due to unescaped user input: ' . $e->getMessage() .
                ' | Raw SQL: ' . $sql,
                0,
                $e
            );
        }

        return response()->json(['user' => $result]);
    }

    // -------------------------------------------------------------------------
    // 场景 13: 支付回调幂等性缺失（重复扣款）
    // 触发: 同一订单被重复处理，抛出业务异常
    // AI 修复方向: 加幂等检查（Redis/DB 唯一键）
    // -------------------------------------------------------------------------
    public function paymentDuplicateCharge(Request $request): mixed
    {
        $orderId = $request->query('order_id', 'ORD-20240101-8888');
        $amount  = (float) $request->query('amount', 299.00);

        // 模拟：没有幂等检查，缓存中已存在处理记录
        $cacheKey = 'payment_processed_' . $orderId;
        if (Cache::has($cacheKey)) {
            $existing = Cache::get($cacheKey);
            throw new \RuntimeException(
                sprintf(
                    'Duplicate payment detected for order %s: ' .
                    'already charged %.2f at %s. ' .
                    'Missing idempotency check before processing payment callback.',
                    $orderId,
                    $existing['amount'],
                    $existing['charged_at']
                )
            );
        }

        // 第一次处理：记录到缓存（模拟入库）
        Cache::put($cacheKey, [
            'amount'     => $amount,
            'charged_at' => now()->toDateTimeString(),
        ], 60);

        // 模拟第二次回调（Sentry 告警场景：支付网关重试触发重复扣款）
        if (Cache::has($cacheKey)) {
            $existing = Cache::get($cacheKey);
            throw new \RuntimeException(
                sprintf(
                    'Duplicate payment detected for order %s: ' .
                    'already charged %.2f at %s. ' .
                    'Missing idempotency check before processing payment callback.',
                    $orderId,
                    $existing['amount'],
                    $existing['charged_at']
                )
            );
        }

        return response()->json(['status' => 'charged', 'order_id' => $orderId]);
    }

    // -------------------------------------------------------------------------
    // 场景 14: 外部 API 调用无超时 + 无重试（服务雪崩）
    // 触发: 外部接口超时，ConnectionException 未捕获直接向上抛
    // AI 修复方向: 加 timeout()、retry()、fallback 降级
    // -------------------------------------------------------------------------
    public function externalApiNoTimeout(): mixed
    {
        // 调用一个不存在的外部服务（模拟生产中第三方 API 超时）
        try {
            $response = Http::timeout(3)->get('http://192.0.2.1/api/inventory');  // 不可路由的 IP
            $data = $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException(
                'External inventory API call failed: ' . $e->getMessage() .
                ' | No timeout/retry/fallback configured. ' .
                'This blocks the entire request thread and may cascade to service outage.',
                0,
                $e
            );
        }

        return response()->json($data);
    }

    // -------------------------------------------------------------------------
    // 场景 15: 用户权限越权访问（水平越权）
    // 触发: 用户 A 可以读取用户 B 的订单，抛出安全异常
    // AI 修复方向: 加 authorizeResource() 或 policy 校验
    // -------------------------------------------------------------------------
    public function unauthorizedDataAccess(Request $request): mixed
    {
        $currentUserId  = 42;   // 模拟当前登录用户
        $requestedOrder = [
            'id'      => 9001,
            'user_id' => 99,    // 属于另一个用户的订单
            'amount'  => 1299.00,
            'status'  => 'paid',
        ];

        // 缺少 owner 校验，直接返回了别人的数据
        if ($requestedOrder['user_id'] !== $currentUserId) {
            throw new \RuntimeException(
                sprintf(
                    'Unauthorized access: user %d attempted to access order %d owned by user %d. ' .
                    'Missing ownership check — horizontal privilege escalation vulnerability.',
                    $currentUserId,
                    $requestedOrder['id'],
                    $requestedOrder['user_id']
                )
            );
        }

        return response()->json($requestedOrder);
    }

    // -------------------------------------------------------------------------
    // 场景 16: 缓存穿透（Cache Stampede）
    // 触发: 热点 key 失效瞬间大量请求同时穿透到 DB，抛出连接池耗尽异常
    // AI 修复方向: 加分布式锁 / 软过期 / 回写空值
    // -------------------------------------------------------------------------
    public function cacheStampede(Request $request): mixed
    {
        $productId = $request->query('product_id', 'HOT-SKU-001');
        $cacheKey  = 'product_detail_' . $productId;

        // 模拟 cache miss 且没有防击穿机制
        $cached = Cache::get($cacheKey);
        if ($cached === null) {
            $lock = Cache::lock('lock_' . $cacheKey, 10);

            if ($lock->get()) {
                // 双重检查
                $cached = Cache::get($cacheKey);
                if ($cached === null) {
                    $cached = ['id' => $productId, 'name' => 'Hot Product', 'stock' => 0];
                    Cache::put($cacheKey, $cached, 60);
                }
                $lock->release();
            } else {
                // 未获取到锁,等待后重试或回退默认值
                usleep(100000); // 100ms
                $cached = Cache::get($cacheKey);
                if ($cached === null) {
                    $cached = ['id' => $productId, 'name' => 'Hot Product', 'stock' => 0];
                }
            }
        }
        return response()->json($cached);
    }

    // -------------------------------------------------------------------------
    // 场景 17: 大文件内存溢出（一次性 load 全表）
    // 触发: 将百万级记录全部加载到 PHP 内存，触发 memory_limit 超限
    // AI 修复方向: 改用 chunk() / lazy() 分批处理
    // -------------------------------------------------------------------------
    public function memoryExhaustionOnBulkExport(): mixed
    {
        // 模拟一次性加载大量数据（生产中是 SELECT * FROM orders 无 LIMIT）
        $fakeRowCount = 2_000_000;
        $memoryPerRow = 512;  // bytes
        $estimatedMB  = ($fakeRowCount * $memoryPerRow) / 1024 / 1024;
        $limitMB      = 128;  // PHP memory_limit

        if ($estimatedMB > $limitMB) {
            throw new \RuntimeException(
                sprintf(
                    'Memory exhaustion during bulk export: loading %s records would require ~%dMB, ' .
                    'exceeding PHP memory_limit of %dMB. ' .
                    'Use chunk() or cursor() for large dataset iteration instead of get().',
                    number_format($fakeRowCount),
                    (int) $estimatedMB,
                    $limitMB
                )
            );
        }

        return response()->json(['exported' => $fakeRowCount]);
    }

    // -------------------------------------------------------------------------
    // 场景 18: N+1 查询问题（循环内查询）
    // 触发: 每个用户单独触发一次 DB 查询，共产生 N+1 次查询，超时崩溃
    // AI 修复方向: with('orders') 预加载，消除 N+1
    // -------------------------------------------------------------------------
    public function nPlusOneQueryProblem(): mixed
    {
        // 模拟 100 个用户，每个用户在循环内各自查一次订单
        $userCount      = 100;
        $queriesPerUser = 1;
        $totalQueries   = 1 + ($userCount * $queriesPerUser);  // 1(users) + 100(orders)
        $thresholdMs    = 500;
        $actualMs       = $totalQueries * 8;  // 模拟每次查询 ~8ms

        if ($actualMs > $thresholdMs) {
            throw new \RuntimeException(
                sprintf(
                    'N+1 query detected: fetching %d users triggered %d SQL queries (%dms total, threshold=%dms). ' .
                    'Missing eager loading — add with(\'orders\') to eliminate N+1 problem.',
                    $userCount,
                    $totalQueries,
                    $actualMs,
                    $thresholdMs
                )
            );
        }

        return response()->json(['users' => $userCount, 'queries' => $totalQueries]);
    }
}
