<div
    x-data="{
        visible: false,
        content: null,
        table: null,
        resizeObserver: null,
        mutationObserver: null,
        scrollHandler: null,
        frame: null,
        init() {
            this.scrollHandler = () => {
                if (this.content && this.$refs.track.scrollLeft !== this.content.scrollLeft) {
                    this.$refs.track.scrollLeft = this.content.scrollLeft
                }
            }
            this.resizeObserver = new ResizeObserver(() => this.schedule())
            this.mutationObserver = new MutationObserver(() => this.schedule())
            this.$nextTick(() => {
                this.mutationObserver.observe(this.$el.closest('.fi-ta-ctn'), { childList: true, subtree: true })
                this.schedule()
            })
        },
        schedule() {
            if (this.frame !== null) return
            this.frame = requestAnimationFrame(() => {
                this.frame = null
                this.refresh()
            })
        },
        refresh() {
            const content = this.$el.closest('.fi-ta-ctn')?.querySelector('.fi-ta-content-ctn')
            if (content !== this.content) {
                this.content?.removeEventListener('scroll', this.scrollHandler)
                this.resizeObserver.disconnect()
                this.content = content
                this.table = null
                if (content) {
                    content.addEventListener('scroll', this.scrollHandler, { passive: true })
                    this.resizeObserver.observe(content)
                }
            }
            const table = content?.querySelector('.fi-ta-table')
            if (table !== this.table) {
                if (this.table) this.resizeObserver.unobserve(this.table)
                this.table = table
                if (table) this.resizeObserver.observe(table)
            }
            this.visible = Boolean(content && content.scrollWidth > content.clientWidth + 1)
            this.$refs.spacer.style.width = `${content?.scrollWidth ?? 0}px`
            if (content) this.$refs.track.scrollLeft = content.scrollLeft
        },
        destroy() {
            if (this.frame !== null) cancelAnimationFrame(this.frame)
            this.content?.removeEventListener('scroll', this.scrollHandler)
            this.resizeObserver?.disconnect()
            this.mutationObserver?.disconnect()
        },
    }"
    x-cloak
    x-show="visible"
>
    <div
        x-ref="track"
        x-on:scroll="if (content && content.scrollLeft !== $el.scrollLeft) content.scrollLeft = $el.scrollLeft"
        tabindex="0"
        aria-label="课程列表横向滚动"
        style="height: 18px; overflow-x: auto; overflow-y: hidden;"
    >
        <div x-ref="spacer" style="height: 1px;"></div>
    </div>
</div>
