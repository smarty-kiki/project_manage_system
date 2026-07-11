<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title or '项目管理系统' }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #333; background: #fff; }
        a { color: #1890ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .btn { display: inline-block; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; text-align: center; transition: all .2s; }
        .btn-primary { background: #1890ff; color: #fff; }
        .btn-primary:hover { background: #40a9ff; text-decoration: none; }
        .btn-outline { background: transparent; color: #1890ff; border: 1px solid #1890ff; }
        .btn-outline:hover { background: #1890ff; color: #fff; text-decoration: none; }
        .btn-lg { padding: 14px 32px; font-size: 16px; }

        /* Container */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }

        /* Navbar */
        .navbar { height: 64px; background: #fff; border-bottom: 1px solid #f0f0f0; position: sticky; top: 0; z-index: 100; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; height: 100%; }
        .navbar-logo { font-size: 18px; font-weight: 700; color: #1890ff; }
        .navbar-links { display: flex; gap: 32px; align-items: center; }
        .navbar-links a { color: #666; font-size: 14px; }
        .navbar-links a:hover { color: #1890ff; }

        /* Hero */
        .hero { padding: 100px 0 80px; background: linear-gradient(135deg, #f5f7fa 0%, #e8edf5 100%); text-align: center; }
        .hero h1 { font-size: 48px; font-weight: 700; color: #1a1a1a; margin-bottom: 20px; line-height: 1.2; }
        .hero p { font-size: 18px; color: #666; max-width: 600px; margin: 0 auto 36px; line-height: 1.6; }
        .hero-actions { display: flex; gap: 16px; justify-content: center; }

        /* Features */
        .features { padding: 80px 0; background: #fff; }
        .section-header { text-align: center; margin-bottom: 56px; }
        .section-header h2 { font-size: 32px; font-weight: 700; color: #1a1a1a; margin-bottom: 12px; }
        .section-header p { font-size: 16px; color: #666; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; }
        .feature-card { padding: 32px; border-radius: 12px; border: 1px solid #f0f0f0; transition: all .2s; }
        .feature-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); border-color: #1890ff; }
        .feature-icon { width: 48px; height: 48px; border-radius: 10px; background: #e6f7ff; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px; }
        .feature-card h3 { font-size: 16px; font-weight: 600; margin-bottom: 8px; color: #1a1a1a; }
        .feature-card p { font-size: 14px; color: #888; line-height: 1.6; }

        /* Workflow */
        .workflow { padding: 80px 0; background: #f5f7fa; }
        .workflow-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-top: 48px; }
        .workflow-step { text-align: center; position: relative; }
        .step-number { width: 48px; height: 48px; border-radius: 50%; background: #1890ff; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; margin: 0 auto 16px; }
        .workflow-step h4 { font-size: 15px; font-weight: 600; margin-bottom: 6px; }
        .workflow-step p { font-size: 13px; color: #888; }
        .step-connector { position: absolute; top: 24px; left: calc(50% + 32px); width: calc(100% - 64px); height: 2px; background: #d9d9d9; }

        /* CTA */
        .cta { padding: 80px 0; background: #fff; text-align: center; }
        .cta h2 { font-size: 32px; font-weight: 700; margin-bottom: 16px; }
        .cta p { font-size: 16px; color: #666; margin-bottom: 32px; }

        /* Footer */
        .footer { padding: 40px 0; background: #001529; color: rgba(255,255,255,.65); text-align: center; font-size: 13px; }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 32px; }
            .hero p { font-size: 16px; }
            .workflow-steps { grid-template-columns: 1fr 1fr; }
            .navbar-links { display: none; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <div class="navbar-logo">PMS</div>
        <div class="navbar-links">
            <a href="#features">功能</a>
            <a href="/account/register">注册</a>
            <a href="/account/login">登录</a>
        </div>
    </div>
</nav>
