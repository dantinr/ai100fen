# 测试站抓取与索引规则

适用域名仅为`ai100.aicsi.cn`，对应`/etc/nginx/sites-available/ai100.aicsi.cn`。这是前台测试/预览站；其Laravel `APP_ENV=production`用于关闭调试等运行安全，不能据此把所有生产域名一并改成这套抓取策略。

## 当前配置与边界

- 测试站`/robots.txt`由Nginx精确location提供`deploy/robots-testing.txt`，内容为`User-agent: *`及`Allow: /`。
- HTTPS server已有一条`X-Robots-Tag: noindex, nofollow`，本次复用，HTTP server补齐同一响应头。`always`覆盖成功、重定向和错误响应；robots location沿用父级响应头，不重新添加导致重复或丢失安全响应头。
- HTTP继续301至同域HTTPS，无跨域跳转；Laravel布局原有`<meta name="robots" content="noindex, nofollow">`与响应头一致。Google必须能抓取页面才能读取noindex，robots.txt本身不放noindex指令，见[Google说明](https://developers.google.com/search/docs/crawling-indexing/block-indexing)。
- `public/robots.txt`和Laravel业务、路由、中间件、模板均未修改，其他生产域名的SEO策略保持原样。该Nginx文件仅供指定测试域名，不作为通用生产模板。
- 已检查Nginx生效配置、Laravel中间件、路由、Controller及Apache备用`.htaccess`，未发现按User-Agent屏蔽爬虫的额外逻辑。课程及下载权限、账号认证、CSRF、注册/登录/改密限流、隐藏文件拒绝访问和TLS均保留；爬虫允许规则不授予私有资源访问权限。
- 检查时DNS A记录直达`39.106.113.140`，响应显示Nginx且未见Cloudflare边缘标记；尚未登录Cloudflare后台，不能据此声称账户中没有相关配置。

## 配置安装与验证

先按既有流程本地push、服务器pull。Git Hook不自动改写`/etc/nginx`；本次站点配置变更需单独备份已启用文件，安装仓库中的`deploy/nginx/ai100.aicsi.cn.conf`到指定测试站点，执行`nginx -t`通过后平滑reload。验证失败时恢复备份，不修改其他站点或应用环境。

在开发机执行：

```sh
node deploy/verify-crawlers.mjs
```

脚本检查首页、课程详情、问题池、robots.txt的HTTPS GET/HEAD、HTTP重定向及五组User-Agent，断言每次响应恰有一条noindex响应头。还检查静态资源、404、隐藏文件拒绝、账号跳转与原安全响应头。User-Agent模拟不等于真实搜索引擎的来源IP验证，也不证明所有边缘安全规则均已检查；脚本不输出Cookie或Token。

## Cloudflare人工核对清单

仅在测试域名接入Cloudflare代理时应用以下清单；不要改变同一Zone中的生产站策略。后台功能入口可能随套餐变化。

1. **托管robots.txt / Content Signals**：确认测试域名未被追加AI爬虫专属`Disallow`；需提供两行原样robots时，移除该域名的托管生成/Worker覆盖。若功能只能按整个Zone切换，使用独立测试Zone或域名隔离，不关闭生产限制。[托管robots说明](https://developers.cloudflare.com/bots/additional-configurations/managed-robots-txt/)
2. **AI Crawl Control / Block AI Bots / AI Labyrinth**：核对测试域名公开GET/HEAD未被统一阻断、挑战、付费墙或替代内容策略覆盖。按测试域名调整可用的bot专属策略，保留生产域名行为。[Bot规则说明](https://developers.cloudflare.com/bots/additional-configurations/custom-rules/)
3. **WAF自定义Bot规则及Super Bot Fight Mode**：在Security Events定位误拦规则，只对测试Host、公开页面GET/HEAD调整bot挑战或跳过Super Bot Fight Mode。不要跳过整套Managed WAF、DDoS、必要限流，不能依据可伪造User-Agent放行后台、私人页面或写操作。[Super Bot Fight Mode例外](https://developers.cloudflare.com/bots/get-started/super-bot-fight-mode/)
4. **普通Bot Fight Mode限制**：它无法通过WAF Skip为单一测试Host开例外。如果现有防护必须保留，用可限定规则的方案或独立测试Zone，不能为抓取关闭整个生产Zone的安全防护。[官方限制](https://developers.cloudflare.com/bots/get-started/bot-fight-mode/)
5. **响应头Transform Rules / Workers / Snippets**：先检查是否已有等效noindex；保留源站响应头即可。如果边缘必须设置，只匹配`http.host eq "ai100.aicsi.cn"`，使用Set替换为`noindex, nofollow`，不用Add叠加；确认没有后续规则删除或改成index。[响应头设置说明](https://developers.cloudflare.com/rules/transform/response-header-modification/)
6. **Redirect / Access / 缓存**：确认测试站公开入口未跳转至生产域名、未要求爬虫交互式挑战，保留私人入口的Access认证；清理该Host旧robots缓存及被缓存的HTML/noindex响应头。最后从公网复测四个URL，并查看真实爬虫的Security Events和日志。此次没有修改Cloudflare后台配置。
