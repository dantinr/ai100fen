# AI100分 PAUL_EXPRESSION_SPECS.md

## 1. 文档定位

本文件定义 Paul 的视觉状态、动作、表情与动画规范。

目标：

> 用极少量动作，建立稳定、可识别、可复用的角色表达系统。

第一阶段不追求复杂角色动画。

只需要让用户明显感知到：

```text
Paul 听懂了
Paul 想到了
Paul 不确定
Paul 有点尴尬
Paul 正在思考
Paul 完成了
```

---

# 2. 原创原则

Paul 必须是原创外星人角色。

可以借鉴：

- 松弛；
- 幽默；
- 机灵；
- 略带调侃感的外星人气质。

禁止：

- 复刻影视角色脸型；
- 复刻影视角色服装；
- 复刻标志性姿势；
- 使用电影剧照或角色截图作为素材；
- 做成可直接识别为现有影视角色的形象。

---

# 3. 基础造型建议

建议：

```text
大头
短脖子或无明显脖子
小身体
大眼睛
细手臂
三到四根手指
轻微歪头
```

脸部应简单，方便 SVG 表情切换。

主要可动区域：

```text
眼睛
眉部或上眼睑
嘴
头部角度
手臂
肩膀
少量装饰元素
```

---

# 4. SVG 分层建议

建议将 Paul 拆成：

```text
paul-root
├── head
├── eyes
│   ├── eye-left
│   └── eye-right
├── eyelids
├── mouth
├── body
├── arm-left
├── arm-right
├── hand-left
├── hand-right
└── fx
    ├── bulb
    ├── sparkle
    ├── sweat
    └── scan
```

这样可通过 CSS class 切换状态。

---

# 5. 状态接口

建议：

```text
idle
listening
thinking
idea
got_it
so_so
awkward
celebrate
typing
sleep
```

组件接口示例：

```text
PaulAvatar(
  state="idea",
  size="medium"
)
```

---

# 6. idle

用途：

默认待机。

动作：

- 轻微呼吸；
- 每 4-8 秒偶尔眨眼；
- 偶尔轻微偏头；
- 不做持续大动作。

视觉：

```text
eyes: neutral
mouth: slight-smile
head: 0deg
arms: relaxed
```

动画：

```text
breath: 3s - 4s
blink: random-like but deterministic interval
```

原则：

> idle 必须安静。

---

# 7. listening

用途：

用户正在输入或刚刚开始说话。

表现：

- 眼睛略睁大；
- 身体轻微前倾；
- 头向一侧倾斜 3-5deg。

视觉：

```text
eyes: attentive
mouth: neutral
head: tilt
```

---

# 8. thinking

用途：

收到用户信息后短暂处理。

表现：

- 眼睛向上偏；
- 头轻微侧转；
- 一只手托下巴或靠近脸部；
- 可出现极小的 3 个点或环形符号。

避免：

- 长时间循环；
- 夸张加载器；
- 假装展示复杂思考过程。

时间：

```text
300ms - 1200ms
```

---

# 9. idea / 灵机一动

用途：

Paul 找到思路、发现路径或形成推荐。

视觉：

```text
eyes: widen + sparkle
mouth: small smile
head: quick raise
arm: one hand slightly raised
fx: bulb / star sparkle
```

动作顺序：

```text
thinking
↓
head raise
↓
eyes brighten
↓
sparkle appears
↓
settle
```

动画总时长：

```text
500ms - 900ms
```

建议效果：

- 头顶出现小灯泡或星形闪光；
- 闪光只出现一次；
- 不持续闪烁。

---

# 10. got_it

用途：

理解、确认、接受。

视觉：

```text
eyes: confident
mouth: slight grin
head: nod
hand: optional OK-like neutral confirmation gesture
```

动作：

```text
nod down
↓
nod up
↓
return neutral
```

总时长：

```text
350ms - 600ms
```

避免：

- 人类手势做得太写实；
- 过度夸张。

---

# 11. so_so

用途：

信息不足、一般般、不完全确定。

视觉：

```text
eyes: half-open
mouth: flat
shoulders: small shrug
hands: slight spread
head: small side movement
```

动作：

```text
shoulder raise
↓
hands spread
↓
head slight left-right
↓
return
```

总时长：

```text
700ms - 1200ms
```

角色感觉：

> “嗯……还差点信息。”

---

# 12. awkward / 尴尬

用途：

没听懂、暂不支持、系统错误。

视觉：

```text
eyes: side glance
mouth: awkward curve
head: slight backward tilt
fx: one sweat drop
hand: scratch head optional
```

动作：

```text
eyes move side
↓
sweat drop appears
↓
head scratch / slight recoil
↓
settle
```

总时长：

```text
700ms - 1200ms
```

原则：

- 可爱但不幼稚；
- 自嘲而不是嘲笑用户。

---

# 13. celebrate

用途：

用户完成 100 分、成功提交问题、完成关键任务。

视觉：

```text
eyes: happy
mouth: grin
arms: raise
fx: small burst / stars
```

动画：

```text
small jump
↓
arms up
↓
star burst
↓
return
```

时长：

```text
700ms - 1200ms
```

禁止：

- 满屏烟花；
- 长时间循环。

---

# 14. typing

用途：

Paul 正在生成回复。

表现：

- 眼睛轻微移动；
- 嘴保持 neutral；
- 手部可做非常轻微动作；
- UI 内显示 typing dots。

Paul 本体不要做复杂“打字”动作。

---

# 15. sleep

用途：

长时间未交互时可选。

表现：

- 眼睛半闭；
- 轻微低头；
- 可有极小的 Z。

但：

第一版可不实现。

---

# 16. 状态切换规则

推荐状态机：

```text
idle
→ listening
→ thinking
→ idea / got_it / so_so / awkward
→ idle
```

成功完成：

```text
got_it
→ celebrate
→ idle
```

避免：

```text
idea → awkward → celebrate → so_so
```

这类无逻辑跳转。

---

# 17. 动画时长规范

建议：

```text
micro response      150-300ms
normal expression   350-700ms
complex expression  700-1200ms
idle loop           3-5s
```

原则：

> 动作短，反馈快。

---

# 18. Motion Reduced

必须支持：

```css
@media (prefers-reduced-motion: reduce)
```

降级为：

- 表情直接切换；
- 不位移；
- 不旋转；
- 不弹跳；
- 不循环。

---

# 19. Theme System 兼容

Paul 的状态语义不变：

```text
idea
got_it
so_so
awkward
```

但视觉可被主题重绘。

## Pop Theme

- 粗黑描边；
- 硬阴影；
- 夸张星形；
- 黄色 / 橙色 FX；
- 漫画动作。

## Future Theme

- 细线；
- Glow；
- HUD；
- Cyan / Green FX；
- 平滑缓动。

状态接口不变。

---

# 20. 表情资源建议

第一版建议只制作：

```text
paul-idle.svg
paul-idea.svg
paul-got-it.svg
paul-so-so.svg
paul-awkward.svg
paul-celebrate.svg
```

如果使用单 SVG 分层，可以不生成多个文件，而通过 class 控制。

优先级：

```text
单 SVG 分层
>
多个 SVG
>
PNG序列
>
GIF
```

---

# 21. 角色尺寸

建议：

```text
navigation/icon: 28-36px
floating trigger: 48-64px
chat header: 48-72px
chat expression: 96-160px
empty state illustration: 160-240px
```

不要让 Paul 长时间占据大面积页面。

---

# 22. 点击区域

Paul 的视觉本体可以小，但点击区域至少：

```text
44 × 44px
```

移动端优先保证易点。

---

# 23. 与 UFO 的关系

Paul 和 UFO 不应互相竞争。

建议关系：

```text
UFO = AI100分 世界观中的交通 / 探索符号
Paul = 会和用户对话的角色
```

可以设计：

- Paul 驾驶 UFO；
- Paul 从 UFO 探头；
- Paul 与 UFO 同时出现在完成页。

但不要每个页面同时大量出现两者。

---

# 24. Codex 实现建议

第一阶段：

1. 建立 PaulAvatar 组件；
2. 支持 state 参数；
3. 使用 SVG + CSS；
4. 实现：
   - idle
   - idea
   - got_it
   - so_so
   - awkward
5. 加 reduced-motion；
6. 接入 PaulChatWidget；
7. 先不接复杂大模型人格系统。

---

# 25. 验收标准

- 四个核心动作肉眼可区分；
- 动作和语义匹配；
- 状态切换不突兀；
- idle 不骚扰用户；
- 动画不阻塞点击；
- 移动端正常；
- reduced-motion 正常；
- Pop Theme 正常；
- 未来 Theme 可以复用同一状态接口；
- 角色形象保持原创，不复刻现有影视角色。

---

# 26. 最终原则

Paul 的动作不是为了炫动画。

每个动作都回答一个问题：

```text
Paul 听懂了吗？
Paul 有想法了吗？
Paul 觉得信息够吗？
Paul 遇到问题了吗？
```

角色表情的价值是：

> 让 AI 的状态变得可感知。
