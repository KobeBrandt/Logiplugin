<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Plugin Marketplace API</title>
    <meta name="description" content="An unofficial read-only REST API for browsing plugins from the Logi Marketplace.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --bg: #fbfbfb;
            --surface: #ffffff;
            --surface-muted: #f4f4f6;
            --code-bg: #f6f5fa;
            --border: rgba(34, 36, 37, 0.1);
            --border-strong: rgba(34, 36, 37, 0.3);
            --text: rgba(34, 36, 37, 0.9);
            --text-muted: rgba(34, 36, 37, 0.6);
            --hover: rgba(0, 0, 0, 0.05);
            --selected: rgba(0, 0, 0, 0.1);
            --accent: #814efa;
            --accent-hover: #6c38ea;
            --accent-soft: rgba(129, 78, 250, 0.1);
            --success: #16a34a;
            --warning: #c2410c;
            --danger: #dc2626;
            --json-key: #6b3fd4;
            --json-string: #0f7b4f;
            --json-number: #b45309;
            --json-literal: #be185d;
            --card-shadow: 1px 1px 4px rgba(0, 0, 0, 0.08);
            --button-shadow: 0 3px 1px -2px rgba(0, 0, 0, 0.2), 0 2px 2px 0 rgba(0, 0, 0, 0.14), 0 1px 5px 0 rgba(0, 0, 0, 0.12);
            --radius: 8px;
            --font: "Outfit", "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            --mono: "Cascadia Code", "SF Mono", Menlo, Consolas, monospace;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                color-scheme: dark;
                --bg: #131315;
                --surface: #1c1c1f;
                --surface-muted: #26262a;
                --code-bg: #17171a;
                --border: rgba(255, 255, 255, 0.1);
                --border-strong: rgba(255, 255, 255, 0.28);
                --text: rgba(255, 255, 255, 0.92);
                --text-muted: rgba(255, 255, 255, 0.6);
                --hover: rgba(255, 255, 255, 0.06);
                --selected: rgba(255, 255, 255, 0.12);
                --accent: #9b74ff;
                --accent-hover: #ad8dff;
                --accent-soft: rgba(155, 116, 255, 0.16);
                --success: #4ade80;
                --warning: #fbbf24;
                --danger: #f87171;
                --json-key: #b9a1ff;
                --json-string: #86efac;
                --json-number: #fcd34d;
                --json-literal: #f9a8d4;
                --card-shadow: none;
            }
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; scroll-padding-top: 24px; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text-muted);
            font-family: var(--font);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }

        code, pre { font-family: var(--mono); font-size: 13px; }

        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

        h1, h2, h3 { margin: 0; color: var(--text); font-weight: 700; line-height: 1.3; }
        h2 { font-size: 20px; }
        h3 { font-size: 16px; }

        /* ---------- Header ---------- */

        .header { padding: 28px 44px; background: var(--bg); border-bottom: 1px solid var(--border); }

        .site-title { font-size: 32px; font-weight: 600; color: var(--text); letter-spacing: -0.01em; }

        /* ---------- Layout ---------- */

        .layout { display: grid; grid-template-columns: 383px minmax(0, 1fr); }

        .sidebar {
            position: sticky;
            top: 0;
            align-self: start;
            max-height: 100vh;
            overflow-y: auto;
            padding: 32px 24px 32px 44px;
            border-right: 1px solid var(--border);
        }

        .filter-group { padding-bottom: 16px; margin-bottom: 20px; border-bottom: 1px solid var(--border); }
        .filter-group:last-child { border-bottom: 0; }
        .filter-group h3 { font-size: 20px; margin-bottom: 14px; }

        .filter-list { display: flex; flex-direction: column; gap: 2px; margin: 0; padding: 0; list-style: none; }

        .filter-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 7px 12px;
            border: 0;
            border-radius: var(--radius);
            background: transparent;
            color: var(--text);
            font: inherit;
            text-align: left;
            cursor: pointer;
        }

        .filter-item:hover { background: var(--hover); text-decoration: none; }
        .filter-item.active { background: var(--selected); color: var(--accent); }

        main { padding: 32px 44px 48px 24px; min-width: 0; }

        section { margin-bottom: 48px; }

        .section-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; }

        .section-lead { margin: 0 0 20px; max-width: 720px; }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            padding: 20px;
        }

        /* ---------- Banner ---------- */

        .banner {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            min-height: 164px;
            margin-bottom: 20px;
            padding: 32px 24px;
            border-radius: var(--radius);
            color: #fff;
            text-align: center;
            background:
                radial-gradient(60% 120% at 0% 0%, rgba(120, 140, 230, 0.9), transparent 60%),
                radial-gradient(50% 120% at 100% 100%, rgba(110, 90, 220, 0.95), transparent 60%),
                radial-gradient(40% 90% at 70% 10%, rgba(80, 120, 240, 0.8), transparent 70%),
                linear-gradient(115deg, #b17ad8 0%, #e2559c 35%, #ec4f9a 55%, #9b5dd6 80%, #7f7fe0 100%);
        }

        .banner h1 { color: #fff; font-size: clamp(24px, 3.2vw, 34px); font-weight: 400; letter-spacing: 0.01em; }

        .base-url {
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: 100%;
            padding: 4px 4px 4px 14px;
            border-radius: var(--radius);
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(6px);
        }

        .base-url code { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #fff; }

        .base-url button {
            padding: 6px 12px;
            border: 0;
            border-radius: 6px;
            background: #fff;
            color: #6c38ea;
            font: inherit;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            cursor: pointer;
        }

        .api-status { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255, 255, 255, 0.9); }
        .api-status::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: rgba(255, 255, 255, 0.6); }
        .api-status.online::before { background: #4ade80; box-shadow: 0 0 6px #4ade80; }
        .api-status.offline::before { background: #fca5a5; }

        /* ---------- Guide content ---------- */

        .flow { display: grid; grid-template-columns: 1fr auto 1fr auto 1fr; gap: 12px; align-items: stretch; }

        .flow-step { display: flex; flex-direction: column; gap: 6px; }
        .flow-step p { margin: 0; }
        .flow-step.highlight { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }

        .flow-icon {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            margin-bottom: 6px;
            border-radius: var(--radius);
            background: var(--accent-soft);
            color: var(--accent);
        }

        .flow-arrow { display: grid; place-items: center; color: var(--text-muted); }

        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 16px; }
        .features p { margin: 4px 0 0; }

        .endpoint { margin-bottom: 20px; padding: 0; overflow: hidden; }

        .endpoint-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
        }

        .method {
            padding: 2px 10px;
            border-radius: 12px;
            background: var(--accent);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .endpoint-path { color: var(--text); font-size: 15px; word-break: break-all; }
        .endpoint-body { padding: 16px 20px 20px; }
        .endpoint-body > p { margin: 0 0 16px; }

        .table-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: var(--text); font-weight: 600; }
        th, td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: top; }
        tr:last-child td { border-bottom: 0; }
        td:first-child { white-space: nowrap; color: var(--text); }
        td code { color: var(--accent); }

        pre {
            margin: 0;
            padding: 16px 18px;
            overflow-x: auto;
            border-radius: var(--radius);
            background: var(--code-bg);
            border: 1px solid var(--border);
            color: var(--text);
            line-height: 1.6;
        }

        .json-key { color: var(--json-key); }
        .json-string { color: var(--json-string); }
        .json-number { color: var(--json-number); }
        .json-literal { color: var(--json-literal); }

        .code-label { margin: 18px 0 8px; color: var(--text); font-weight: 600; }

        .two-col { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 8px; }
        .two-col h3 { margin-bottom: 8px; }

        .status-code { font-family: var(--mono); font-weight: 700; }
        .status-code.ok { color: var(--success); }
        .status-code.warn { color: var(--warning); }
        .status-code.err { color: var(--danger); }

        footer { padding: 20px 44px 32px; border-top: 1px solid var(--border); font-size: 13px; }

        /* ---------- Small screens ---------- */

        @media (max-width: 900px) {
            .header { padding: 20px 16px; }
            .site-title { font-size: 24px; }

            .layout { grid-template-columns: minmax(0, 1fr); }
            .sidebar { display: none; }
            main { padding: 16px 16px 40px; }

            .flow { grid-template-columns: 1fr; }
            .flow-arrow { transform: rotate(90deg); }

            footer { padding: 20px 16px 32px; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="site-title">Plugin Marketplace API</div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <div class="filter-group">
                <h3>Guide</h3>
                <ul class="filter-list" id="guide-nav">
                    <li><a class="filter-item active" href="#overview">Overview</a></li>
                    <li><a class="filter-item" href="#how-it-works">How it works</a></li>
                    <li><a class="filter-item" href="#endpoints">Endpoints</a></li>
                    <li><a class="filter-item" href="#caching">Caching &amp; errors</a></li>
                </ul>
            </div>

            <div class="filter-group">
                <h3>Endpoints</h3>
                <ul class="filter-list">
                    <li><a class="filter-item" href="#list-plugins"><code>GET /plugins</code></a></li>
                    <li><a class="filter-item" href="#show-plugin"><code>GET /plugins/{name}</code></a></li>
                </ul>
            </div>
        </aside>

        <main>
            <section id="overview">
                <div class="banner">
                    <h1>Browse Marketplace Plugins With One Simple API</h1>
                    <div class="base-url">
                        <code id="base-url">{{ $apiUrl }}/plugins</code>
                        <button type="button" data-copy="#base-url">Copy</button>
                    </div>
                    <span class="api-status" id="api-status" role="status">Checking API…</span>
                </div>
                <p class="section-lead">
                    A read-only REST API for the plugins listed on the Logi Marketplace.
                    Search, filter by platform or category, and get full plugin details as clean JSON.
                    You don't need an API key.
                </p>
            </section>

            <section id="how-it-works">
                <div class="section-head"><h2>How it works</h2></div>
                <p class="section-lead">
                    This service sits between your app and the marketplace. It fetches the marketplace data,
                    cleans it up, and serves it through a small, stable API.
                </p>

                <div class="flow">
                    <div class="card flow-step">
                        <div class="flow-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
                        </div>
                        <h3>Your app</h3>
                        <p>Sends a normal HTTP <code>GET</code> request, with optional filters in the query string.</p>
                    </div>
                    <div class="flow-arrow" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </div>
                    <div class="card flow-step highlight">
                        <div class="flow-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 7v5c0 4.5 3.4 8.4 8 9 4.6-.6 8-4.5 8-9V7z"/><path d="m9 12 2 2 4-4"/></svg>
                        </div>
                        <h3>This API</h3>
                        <p>Checks your input, filters and pages the results, and keeps marketplace data cached for {{ $serverCacheMinutes }} minutes.</p>
                    </div>
                    <div class="flow-arrow" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </div>
                    <div class="card flow-step">
                        <div class="flow-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h18l-1.5 10.5a2 2 0 0 1-2 1.5h-11a2 2 0 0 1-2-1.5z"/><path d="M8 9V7a4 4 0 0 1 8 0v2"/></svg>
                        </div>
                        <h3>Logi Marketplace</h3>
                        <p>The original source. It's only contacted when the cache is empty or expired.</p>
                    </div>
                </div>

                <div class="features">
                    <div class="card"><h3>Fast</h3><p>Most requests come straight from the cache, without waiting on the marketplace.</p></div>
                    <div class="card"><h3>Clean data</h3><p>Plugin fields are flattened into a consistent shape, including platforms and download counts.</p></div>
                    <div class="card"><h3>Versioned</h3><p>Every URL starts with <code>/v1</code>, so future breaking changes won't affect your integration.</p></div>
                </div>
            </section>

            <section id="endpoints">
                <div class="section-head"><h2>Endpoints</h2></div>
                <p class="section-lead">Both endpoints return JSON. Send <code>Accept: application/json</code> so errors come back as JSON too.</p>

                <div class="card endpoint" id="list-plugins">
                    <div class="endpoint-head">
                        <span class="method">GET</span>
                        <code class="endpoint-path">/api/v1/plugins</code>
                    </div>
                    <div class="endpoint-body">
                        <p>Lists plugins, one page at a time. All query parameters are optional and can be combined.</p>
                        <div class="table-wrap">
                            <table>
                                <thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead>
                                <tbody>
                                    <tr><td><code>search</code></td><td>string</td><td>Matches the plugin name or display name. Case-insensitive, up to 100 characters.</td></tr>
                                    <tr><td><code>platform</code></td><td>string</td><td>Only plugins for this platform, for example <code>Windows</code> or <code>MacOS</code>.</td></tr>
                                    <tr><td><code>category</code></td><td>string</td><td>Only plugins in this category, for example <code>Gaming</code>.</td></tr>
                                    <tr><td><code>page</code></td><td>integer</td><td>Page number, starting at 1. Default: 1.</td></tr>
                                    <tr><td><code>per_page</code></td><td>integer</td><td>Results per page, from 1 to {{ $maxPerPage }}. Default: {{ $defaultPerPage }}.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="code-label">Example request</p>
                        <pre><code>curl "{{ $apiUrl }}/plugins?search=dice&amp;category=Gaming"</code></pre>

                        <p class="code-label">Example response <span style="font-weight: 400">(the <code>meta.links</code> array is left out)</span></p>
                        <pre><code class="json">{
  "data": [
    {
      "id": 4942,
      "name": "Dice",
      "displayName": "Dice",
      "description": "Allows quick virtual dice rolling.",
      "categories": ["Gaming"],
      "icon": "https://marketplace.logi.com/resources/16/Dice_65255eb975.png",
      "versions": ["1.2.1"],
      "platforms": ["MacOS", "Windows"],
      "firstPublicAt": "2025-10-06T17:04:48.177Z",
      "downloads": 8352
    }
  ],
  "links": {
    "first": "{{ $apiUrl }}/plugins?search=dice&category=Gaming&page=1",
    "last": "{{ $apiUrl }}/plugins?search=dice&category=Gaming&page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "{{ $apiUrl }}/plugins",
    "per_page": {{ $defaultPerPage }},
    "to": 1,
    "total": 1
  }
}</code></pre>
                    </div>
                </div>

                <div class="card endpoint" id="show-plugin">
                    <div class="endpoint-head">
                        <span class="method">GET</span>
                        <code class="endpoint-path">/api/v1/plugins/{name}</code>
                    </div>
                    <div class="endpoint-body">
                        <p>
                            Returns one plugin, looked up by its <code>name</code> (not the display name).
                            It has all the list fields plus author, links, requirements, and the full version history
                            (here <code>versions</code> is a list of objects instead of version numbers).
                        </p>

                        <p class="code-label">Example request</p>
                        <pre><code>curl "{{ $apiUrl }}/plugins/Dice"</code></pre>

                        <p class="code-label">Example response</p>
                        <pre><code class="json">{
  "data": {
    "id": 4942,
    "name": "Dice",
    "displayName": "Dice",
    "description": "Allows quick virtual dice rolling.",
    "categories": ["Gaming"],
    "icon": "https://marketplace.logi.com/resources/16/Dice_65255eb975.png",
    "versions": [
      {
        "version": "1.2.1",
        "fileUrl": "https://marketplace.logi.com/resources/16/Dice_1_2_1_33fbfc1963.lplug4",
        "minLPSVersion": "6",
        "platforms": ["MacOS", "Windows"]
      }
    ],
    "platforms": ["MacOS", "Windows"],
    "firstPublicAt": "2025-10-06T17:04:48.177Z",
    "downloads": 8352,
    "author": { "name": "KBrandt.dev", "supportUrl": "" },
    "homeUrl": "",
    "requirements": [],
    "capabilities": [],
    "supportedFeatures": []
  }
}</code></pre>
                    </div>
                </div>
            </section>

            <section id="caching">
                <div class="section-head"><h2>Caching &amp; errors</h2></div>
                <p class="section-lead">What to expect when responses are reused, and how errors look.</p>

                <div class="two-col">
                    <div class="card">
                        <h3>Caching</h3>
                        <div class="table-wrap">
                            <table>
                                <tbody>
                                    <tr><td>Server cache</td><td>Marketplace data is kept for <strong>{{ $serverCacheMinutes }} minutes</strong>, so new plugins can take that long to show up.</td></tr>
                                    <tr><td>Your cache</td><td>Successful responses send <code>Cache-Control: public, max-age=300</code>, so you can reuse them for 5 minutes.</td></tr>
                                    <tr><td>ETag</td><td>Send the <code>ETag</code> back as <code>If-None-Match</code> and you'll get <code>304 Not Modified</code> if nothing changed.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Status codes</h3>
                        <div class="table-wrap">
                            <table>
                                <tbody>
                                    <tr><td><span class="status-code ok">200</span></td><td>Success.</td></tr>
                                    <tr><td><span class="status-code ok">304</span></td><td>Not modified. Use your cached copy.</td></tr>
                                    <tr><td><span class="status-code warn">404</span></td><td>No plugin with that name, or an unknown URL.</td></tr>
                                    <tr><td><span class="status-code warn">405</span></td><td>Wrong method. Only <code>GET</code> and <code>HEAD</code> are allowed.</td></tr>
                                    <tr><td><span class="status-code warn">422</span></td><td>A query parameter is invalid. See <code>errors</code>.</td></tr>
                                    <tr><td><span class="status-code err">502</span></td><td>The marketplace couldn't be reached. Try again later.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <p class="code-label">Every error has a <code>message</code>. Invalid input also includes <code>errors</code>.</p>
                <pre><code class="json">{
  "message": "The per page field must not be greater than {{ $maxPerPage }}.",
  "errors": {
    "per_page": ["The per page field must not be greater than {{ $maxPerPage }}."]
  }
}</code></pre>
            </section>
        </main>
    </div>

    <footer>
        An unofficial community project. Not affiliated with or endorsed by Logitech.
        Plugin data comes from the public Logi Marketplace.
    </footer>

    <script>
        const API_URL = @json($apiUrl);
    </script>
    @verbatim
    <script>
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function highlightJson(json) {
            return escapeHtml(json).replace(
                /(&quot;(?:[^&]|&(?!quot;))*&quot;)(\s*:)?|\b(true|false|null)\b|-?\b\d+(?:\.\d+)?\b/g,
                (match, string, colon, literal) => {
                    if (string) {
                        return colon
                            ? `<span class="json-key">${string}</span>${colon}`
                            : `<span class="json-string">${string}</span>`;
                    }

                    return literal
                        ? `<span class="json-literal">${match}</span>`
                        : `<span class="json-number">${match}</span>`;
                },
            );
        }

        async function checkStatus() {
            const status = document.getElementById('api-status');

            try {
                const response = await fetch(`${API_URL}/plugins?per_page=1`, { headers: { Accept: 'application/json' } });
                status.classList.add(response.ok ? 'online' : 'offline');
                status.textContent = response.ok ? 'API online' : 'Marketplace unavailable';
            } catch (error) {
                status.classList.add('offline');
                status.textContent = 'API offline';
            }
        }

        function highlightNav() {
            const guideLinks = [...document.querySelectorAll('#guide-nav a')];

            const observer = new IntersectionObserver((entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) continue;

                    const id = entry.target.id;
                    guideLinks.forEach((link) => link.classList.toggle('active', link.hash === `#${id}`));
                }
            }, { rootMargin: '-30% 0px -60% 0px' });

            document.querySelectorAll('main section').forEach((section) => observer.observe(section));
        }

        document.querySelectorAll('[data-copy]').forEach((button) => {
            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(document.querySelector(button.dataset.copy).textContent);
                    button.textContent = 'Copied';
                } catch (error) {
                    button.textContent = 'Press Ctrl+C';
                }
                setTimeout(() => { button.textContent = 'Copy'; }, 1500);
            });
        });

        document.querySelectorAll('code.json').forEach((block) => {
            block.innerHTML = highlightJson(block.textContent);
        });

        checkStatus();
        highlightNav();
    </script>
    @endverbatim
</body>
</html>
