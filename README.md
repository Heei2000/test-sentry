## Laravel 10 + Sentry 报错演示
本仓库提供极简的 Laravel 10 项目，用来触发多种后端错误，便于验证 Sentry 报警、自动修复流程，以及后续提交到 GitHub/Jira。

### 准备
- **环境**: PHP 8.1+、Composer、Node(可选，仅需前端构建时)。
- **安装依赖**:
  - 后端依赖已通过 Composer 安装完毕；如需重新安装：`composer install`
- **环境变量**:
  - 复制 `.env.example` 为 `.env`，并设置 `SENTRY_LARAVEL_DSN`（同时可同值写入 `SENTRY_DSN` 以兼容检测）。

### 本地运行
- 启动本地服务器：`php artisan serve --host 127.0.0.1 --port 9005`（默认访问 `http://127.0.0.1:9005`）。
- 打开下列演示路由即可向 Sentry 上报：
  - **抛出运行时异常**: `/error-demo/exception`
  - **除零错误**: `/error-demo/divide-by-zero`
  - **手动捕获消息（不抛异常）**: `/error-demo/capture-message`

### 与 Sentry 集成说明
- 已安装 `sentry/sentry-laravel`，默认会自动捕获未处理异常。
- 设置 `.env` 中的 `SENTRY_LARAVEL_DSN` 后，访问演示路由即可在 Sentry 面板看到事件。
- `capture-message` 路由演示了在代码中手动调用 `app('sentry')->captureMessage(...)`。

### 可能的扩展
- 如需模拟语法/类型错误，可在本地分支里故意写入错误代码并访问对应路由；避免将不可解析的语法错误直接提交到主分支，以免阻塞 CI/CD。
- 如需更多场景（数据库异常、外部 HTTP 超时等），可在 `app/Http/Controllers/ErrorDemoController.php` 中添加新的方法并在 `routes/web.php` 注册。
