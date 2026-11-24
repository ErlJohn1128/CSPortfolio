<script>
    (() => {
        const previousTheme = localStorage.getItem('theme');

        if (previousTheme === 'light' || previousTheme === 'dark') {
            localStorage.setItem('flux.appearance', previousTheme);
            localStorage.removeItem('theme');
        }
    })();
</script>
@fluxAppearance
