const screens = [
    { key: 'overview', file: '01-overview.html', title: 'SEO overview' },
    { key: 'site-defaults', file: '02-site-defaults.html', title: 'Site defaults' },
    { key: 'content-types', file: '03-content-types.html', title: 'Content types' },
    { key: 'resource-defaults', file: '04-resource-defaults.html', title: 'Resource defaults' },
    { key: 'record-metadata', file: '05-record-metadata.html', title: 'Record metadata' },
    { key: 'ai-suggestion', file: '06-ai-suggestion.html', title: 'AI suggestion' },
    { key: 'diagnostics', file: '07-diagnostics.html', title: 'SEO checks' },
    { key: 'diagnostic-detail', file: '08-diagnostic-detail.html', title: 'Issue guidance' },
    { key: 'sitemap-robots', file: '09-sitemap-robots.html', title: 'Sitemap & robots' },
    { key: 'permissions', file: '10-permissions.html', title: 'Role permissions' },
];

const settingsTabs = [
    ['overview', 'Overview'],
    ['site-defaults', 'Site defaults'],
    ['content-types', 'Content types'],
    ['diagnostics', 'SEO checks'],
    ['sitemap-robots', 'Sitemap & robots'],
];

function tabMarkup(current) {
    return settingsTabs.map(([key, label]) => {
        const screen = screens.find(item => item.key === key);
        return `<a class="settings-tab ${current === key || (current === 'resource-defaults' && key === 'content-types') || (current === 'diagnostic-detail' && key === 'diagnostics') ? 'active' : ''}" href="${screen.file}">${label}</a>`;
    }).join('');
}

function resourceTabMarkup(current) {
    return `
        <a class="settings-tab" href="#">Content</a>
        <a class="settings-tab" href="#">Cast & relationships</a>
        <a class="settings-tab active" href="05-record-metadata.html">SEO</a>
        ${current === 'ai-suggestion' ? '<span class="settings-tab" style="margin-left:auto">AI suggestion review</span>' : ''}
    `;
}

function shell(content, current) {
    const index = screens.findIndex(item => item.key === current);
    const previous = screens[(index - 1 + screens.length) % screens.length];
    const next = screens[(index + 1) % screens.length];
    const screen = screens[index];
    const recordMode = ['record-metadata', 'ai-suggestion'].includes(current);
    const roleMode = current === 'permissions';
    const breadcrumbs = recordMode
        ? '<span>Movies</span><span class="crumb-separator">/</span><span>Aurora Station</span><span class="crumb-separator">/</span><strong>SEO</strong>'
        : roleMode
            ? '<span>Settings</span><span class="crumb-separator">/</span><span>Roles</span><span class="crumb-separator">/</span><strong>SEO Manager</strong>'
            : `<span>Settings</span><span class="crumb-separator">/</span><span>SEO</span><span class="crumb-separator">/</span><strong>${screen.title}</strong>`;

    return `
        <div class="prototype-notice">Rough outline - not production markup or copy</div>
        <div class="app-shell">
            <aside class="sidebar">
                <a class="brand" href="index.html"><span class="aura-logo">AURA</span><span class="brand-mark"></span></a>
                <nav class="sidebar-nav" aria-label="Administration">
                    <div class="nav-group">
                        <div class="nav-label">Workspace</div>
                        <a class="nav-link" href="#"><span class="nav-icon">D</span>Dashboard</a>
                        <a class="nav-link ${recordMode ? 'active' : ''}" href="05-record-metadata.html"><span class="nav-icon">M</span>Movies</a>
                        <a class="nav-link" href="#"><span class="nav-icon">A</span>Actors</a>
                        <a class="nav-link" href="#"><span class="nav-icon">G</span>Genres</a>
                    </div>
                    <div class="nav-group">
                        <div class="nav-label">Aura</div>
                        <a class="nav-link" href="#"><span class="nav-icon">U</span>Users</a>
                        <a class="nav-link ${roleMode ? 'active' : ''}" href="10-permissions.html"><span class="nav-icon">R</span>Roles</a>
                        <a class="nav-link" href="#"><span class="nav-icon">F</span>Files</a>
                        <a class="nav-link" href="#"><span class="nav-icon">O</span>Options</a>
                    </div>
                    <div class="nav-group">
                        <div class="nav-label">Settings</div>
                        <a class="nav-link" href="#"><span class="nav-icon">S</span>General</a>
                        <a class="nav-link" href="#"><span class="nav-icon">AI</span>AI</a>
                        <a class="nav-link ${recordMode || roleMode ? '' : 'active'}" href="01-overview.html"><span class="nav-icon">SEO</span>SEO</a>
                    </div>
                </nav>
                <div class="account"><div class="avatar">DA</div><div class="account-copy"><strong>Demo Admin</strong><span>Aura Demo</span></div><span>...</span></div>
            </aside>
            <div class="workspace">
                <header class="topbar">
                    <div class="breadcrumbs">${breadcrumbs}</div>
                    <div class="top-actions"><a class="icon-button" href="index.html" title="Prototype index">::</a><button class="icon-button" type="button" title="Notifications">o</button></div>
                </header>
                <div class="page">
                    ${roleMode ? '' : `<nav class="settings-tabs" aria-label="${recordMode ? 'Movie editor' : 'SEO settings'}">${recordMode ? resourceTabMarkup(current) : tabMarkup(current)}</nav>`}
                    ${content}
                </div>
            </div>
        </div>
        <nav class="prototype-screen-nav" aria-label="Prototype screens">
            <a href="${previous.file}" title="Previous screen">&#8592;</a>
            <div class="prototype-screen-label"><strong>${index + 1} / ${screens.length} - ${screen.title}</strong><span>Use Alt + arrow keys to change screens</span></div>
            <a href="${next.file}" title="Next screen">&#8594;</a>
        </nav>
    `;
}

function installShell() {
    const current = document.body.dataset.screen;
    if (!current) return;
    const content = document.querySelector('#prototype-content');
    if (!content) return;
    document.body.innerHTML = shell(content.outerHTML, current);
    document.title = `${screens.find(item => item.key === current)?.title ?? 'SEO'} - Aura SEO prototype`;
}

function installScreenNavigation() {
    const current = document.body.dataset.screen;
    const index = screens.findIndex(item => item.key === current);
    document.addEventListener('keydown', event => {
        if (!event.altKey || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        const target = event.target;
        if (target instanceof HTMLElement && target.matches('input, textarea, select, [contenteditable]')) return;
        const delta = event.key === 'ArrowLeft' ? -1 : 1;
        const destination = screens[(index + delta + screens.length) % screens.length];
        window.location.href = destination.file;
    });
}

function installVariants() {
    const variants = [...document.querySelectorAll('[data-variant]')];
    if (!variants.length) return;

    const options = [
        { key: 'guided', label: 'A - Guided setup' },
        { key: 'workspace', label: 'B - Workspace (recommended)' },
        { key: 'compact', label: 'C - Compact operations' },
    ];
    const requested = new URLSearchParams(window.location.search).get('variant');
    let currentIndex = Math.max(0, options.findIndex(item => item.key === requested));
    if (requested === null) currentIndex = 1;

    const switcher = document.createElement('div');
    switcher.className = 'variant-switcher';
    switcher.innerHTML = '<button type="button" data-variant-prev aria-label="Previous variant">&#8592;</button><div class="variant-label"><strong></strong><span>Use arrow keys to compare layouts</span></div><button type="button" data-variant-next aria-label="Next variant">&#8594;</button>';
    document.body.appendChild(switcher);

    function show(index, updateUrl = true) {
        currentIndex = (index + options.length) % options.length;
        const option = options[currentIndex];
        variants.forEach(item => item.classList.toggle('active', item.dataset.variant === option.key));
        switcher.querySelector('.variant-label strong').textContent = option.label;
        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('variant', option.key);
            window.history.replaceState({}, '', url);
        }
    }

    switcher.querySelector('[data-variant-prev]').addEventListener('click', () => show(currentIndex - 1));
    switcher.querySelector('[data-variant-next]').addEventListener('click', () => show(currentIndex + 1));
    document.addEventListener('keydown', event => {
        if (event.altKey || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        const target = event.target;
        if (target instanceof HTMLElement && target.matches('input, textarea, select, [contenteditable]')) return;
        show(currentIndex + (event.key === 'ArrowLeft' ? -1 : 1));
    });
    show(currentIndex, requested === null);
}

function installFilters() {
    document.querySelectorAll('[data-filter]').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-filter]').forEach(item => item.classList.remove('active'));
            button.classList.add('active');
            const filter = button.dataset.filter;
            document.querySelectorAll('[data-severity]').forEach(issue => {
                issue.hidden = filter !== 'all' && issue.dataset.severity !== filter;
            });
        });
    });
}

function installCounters() {
    document.querySelectorAll('[data-count-target]').forEach(field => {
        const target = document.querySelector(field.dataset.countTarget);
        const update = () => { if (target) target.textContent = `${field.value.length} / ${field.maxLength}`; };
        field.addEventListener('input', update);
        update();
    });
}

function installAiDemo() {
    const generate = document.querySelector('[data-ai-generate]');
    const apply = document.querySelector('[data-ai-apply]');
    const state = document.querySelector('[data-ai-state]');
    if (generate) {
        generate.addEventListener('click', () => {
            generate.disabled = true;
            generate.textContent = 'Generating suggestion...';
            setTimeout(() => {
                generate.disabled = false;
                generate.textContent = 'Generate another';
                if (state) state.textContent = 'Suggestion ready';
                document.querySelector('[data-ai-result]')?.removeAttribute('hidden');
            }, 650);
        });
    }
    if (apply) {
        apply.addEventListener('click', () => {
            apply.textContent = 'Applied to form';
            apply.classList.remove('primary');
            apply.classList.add('soft');
            if (state) state.textContent = 'Applied to the form. Save the movie when ready.';
        });
    }
}

const currentScreen = document.body.dataset.screen;
installShell();
document.body.dataset.screen = currentScreen;
installScreenNavigation();
installVariants();
installFilters();
installCounters();
installAiDemo();
