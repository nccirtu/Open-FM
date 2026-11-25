<script>
    (() => {
        const root = document.documentElement
        const timeoutMs = 5000
        const startedAt = Date.now()

        const reveal = () => root.setAttribute('data-fi-wg-ready', '')

        const isReady = () => {
            const grid = document.querySelector('.fi-wg-grid')

            if (! grid) {
                return true
            }

            const initialised = /(^|\s)gs-\d+(\s|$)/.test(grid.className)

            const styled = Array.from(document.styleSheets).some(
                (sheet) => sheet.href && sheet.href.includes('filament-widget-grid-styles'),
            )

            const adapted = Array.from(grid.querySelectorAll(':scope > .grid-stack-item')).every(
                (item) => /(^|\s)fi-wg-cell-(portrait|split|landscape)(\s|$)/.test(item.className),
            )

            return initialised && styled && adapted
        }

        if (isReady()) {
            reveal()

            return
        }

        const timer = setInterval(() => {
            if (isReady() || Date.now() - startedAt > timeoutMs) {
                clearInterval(timer)
                reveal()
            }
        }, 40)
    })()
</script>
