<?php
/**
 * ABIS Components Library
 * Reusable UI Components for consistent design across the portal
 */

/**
 * Stat Card Component
 * Displays KPI metrics with icon, title, value, and optional change indicator
 */
function renderStatCard($icon, $title, $value, $change = null, $changeType = 'positive', $bgColor = 'orange') {
    $iconBgColor = $bgColor === 'orange' ? 'bg-orange-light' : ($bgColor === 'success' ? 'bg-success-light' : 'bg-info-light');
    $iconColor = $bgColor === 'orange' ? 'icon-orange' : ($bgColor === 'success' ? 'icon-success' : 'icon-info');
    
    echo <<<HTML
    <div class="stat-card bg-primary border border-light shadow-md">
        <div class="stat-card-header">
            <div class="stat-icon-wrapper icon-bg $iconBgColor">
                <i class="$icon $iconColor icon-lg"></i>
            </div>
            <div class="stat-info">
                <p class="stat-title text-secondary">$title</p>
                <h3 class="stat-value text-primary">$value</h3>
            </div>
        </div>
HTML;
    
    if ($change !== null) {
        $changeIcon = $changeType === 'positive' ? 'fa-arrow-up' : 'fa-arrow-down';
        $changeColor = $changeType === 'positive' ? 'text-success' : 'text-danger';
        echo <<<HTML
        <div class="stat-footer $changeColor">
            <i class="fas $changeIcon"></i>
            <span>$change from last month</span>
        </div>
HTML;
    }
    
    echo '</div>';
}

/**
 * Action Badge Component
 * Displays status badges with appropriate colors
 */
function renderBadge($text, $type = 'primary', $icon = null) {
    $classList = match($type) {
        'success' => 'bg-success-light text-success',
        'warning' => 'bg-warning-light text-warning',
        'danger' => 'bg-danger-light text-danger',
        'info' => 'bg-info-light text-info',
        default => 'bg-blue-light text-blue'
    };
    
    $iconHtml = $icon ? "<i class=\"fas $icon\"></i> " : '';
    
    echo <<<HTML
    <span class="badge $classList">
        $iconHtml$text
    </span>
HTML;
}

/**
 * Table Header Component
 * Creates consistent table headers across the portal
 */
function renderTableHeader($columns, $sortable = false) {
    echo '<thead class="table-header bg-tertiary">';
    echo '<tr>';
    
    foreach ($columns as $column) {
        $icon = $sortable ? '<i class="fas fa-sort text-tertiary"></i>' : '';
        echo <<<HTML
        <th class="table-header-cell">
            <div class="table-header-content">
                $column
                $icon
            </div>
        </th>
HTML;
    }
    
    echo '</tr>';
    echo '</thead>';
}

/**
 * Table Row with Actions Component
 */
function renderTableRow($rowData, $actions = []) {
    echo '<tr class="table-row">';
    
    foreach ($rowData as $cell) {
        echo "<td class=\"table-cell\">$cell</td>";
    }
    
    if (!empty($actions)) {
        echo '<td class="table-cell table-actions">';
        foreach ($actions as $action) {
            echo $action;
        }
        echo '</td>';
    }
    
    echo '</tr>';
}

/**
 * Action Button Component
 * Creates consistent buttons with icons
 */
function renderActionButton($label, $icon, $href = '#', $type = 'primary', $size = 'md', $onClick = '') {
    $classList = "btn btn-$type btn-$size";
    $onClickAttr = $onClick ? "onclick=\"$onClick\"" : '';
    
    echo <<<HTML
    <a href="$href" class="$classList" $onClickAttr>
        <i class="fas $icon"></i>
        <span>$label</span>
    </a>
HTML;
}

/**
 * Modal Component - Opening
 */
function renderModalStart($modalId, $title, $size = 'md') {
    $sizeClass = $size === 'lg' ? 'modal-lg' : ($size === 'sm' ? 'modal-sm' : '');
    
    echo <<<HTML
    <div id="$modalId" class="modal $sizeClass" style="display: none;">
        <div class="modal-content bg-primary">
            <div class="modal-header border-light">
                <h3 class="text-primary">$title</h3>
                <button class="modal-close" onclick="closeModal('$modalId')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
HTML;
}

/**
 * Modal Component - Closing
 */
function renderModalEnd($modalId, $submitText = 'Save', $cancelText = 'Cancel') {
    echo <<<HTML
            </div>
            <div class="modal-footer border-light">
                <button class="btn btn-secondary" onclick="closeModal('$modalId')">
                    $cancelText
                </button>
                <button class="btn btn-primary" onclick="submitForm('$modalId')">
                    $submitText
                </button>
            </div>
        </div>
    </div>
HTML;
}

/**
 * Alert Component
 */
function renderAlert($message, $type = 'info', $icon = null, $dismissible = true) {
    $className = match($type) {
        'success' => 'alert-success bg-success-light text-success',
        'warning' => 'alert-warning bg-warning-light text-warning',
        'danger' => 'alert-danger bg-danger-light text-danger',
        default => 'alert-info bg-info-light text-info'
    };
    
    $defaultIcon = match($type) {
        'success' => 'fa-check-circle',
        'warning' => 'fa-exclamation-circle',
        'danger' => 'fa-times-circle',
        default => 'fa-info-circle'
    };
    
    $icon = $icon ?? $defaultIcon;
    $dismissBtn = $dismissible ? '<button class="alert-dismiss" onclick="this.parentElement.style.display=\'none\'"><i class="fas fa-times"></i></button>' : '';
    
    echo <<<HTML
    <div class="alert $className" role="alert">
        <div class="alert-content">
            <i class="fas $icon"></i>
            <span>$message</span>
        </div>
        $dismissBtn
    </div>
HTML;
}

/**
 * Tabs Navigation Component
 */
function renderTabsStart($tabs, $activeTab = 0) {
    echo '<div class="tabs-wrapper">';
    echo '<nav class="tabs-navigation border-light">';
    
    foreach ($tabs as $index => $tab) {
        $activeClass = $index === $activeTab ? 'tab-active' : '';
        $tabIcon = isset($tab['icon']) ? "<i class=\"fas {$tab['icon']}\"></i>" : '';
        echo <<<HTML
        <button class="tab-button $activeClass" data-tab="$index" onclick="switchTab($index)">
            $tabIcon
            <span>{$tab['label']}</span>
        </button>
HTML;
    }
    
    echo '</nav>';
    echo '<div class="tabs-content">';
}

/**
 * Tabs Navigation Component - End
 */
function renderTabsEnd() {
    echo '</div>';
    echo '</div>';
}

/**
 * Tab Content Component
 */
function renderTabPane($index, $content, $active = false) {
    $activeClass = $active ? 'tab-pane-active' : '';
    echo <<<HTML
    <div class="tab-pane $activeClass" data-tab-pane="$index">
        $content
    </div>
HTML;
}

/**
 * Status Indicator Component
 */
function renderStatusIndicator($status, $label = '') {
    $color = match($status) {
        'active' => 'success',
        'online' => 'success',
        'inactive' => 'warning',
        'offline' => 'danger',
        'pending' => 'warning',
        'error' => 'danger',
        default => 'info'
    };
    
    $displayLabel = $label ?: ucfirst($status);
    
    echo <<<HTML
    <div class="status-indicator">
        <span class="status-dot status-$color"></span>
        <span class="status-label">$displayLabel</span>
    </div>
HTML;
}

/**
 * Grid Card Component - Wrapper
 */
function renderGridStart($columns = 3) {
    echo "<div class=\"grid-wrapper grid-cols-$columns\">";
}

/**
 * Grid Card Component - End
 */
function renderGridEnd() {
    echo '</div>';
}

/**
 * Grid Card Component - Single Card
 */
function renderGridCard($title, $content, $footer = '') {
    echo <<<HTML
    <div class="grid-card bg-primary border border-light shadow-md">
        <div class="card-header border-light">
            <h4 class="text-primary">$title</h4>
        </div>
        <div class="card-body">
            $content
        </div>
HTML;
    
    if ($footer) {
        echo "<div class=\"card-footer border-light\">$footer</div>";
    }
    
    echo '</div>';
}

/**
 * Section Header Component
 */
function renderSectionHeader($title, $subtitle = '', $actionButton = '') {
    echo <<<HTML
    <div class="section-header">
        <div class="section-header-content">
            <h2 class="section-title text-primary">$title</h2>
HTML;
    
    if ($subtitle) {
        echo "<p class=\"section-subtitle text-secondary\">$subtitle</p>";
    }
    
    echo '</div>';
    
    if ($actionButton) {
        echo "<div class=\"section-actions\">$actionButton</div>";
    }
    
    echo '</div>';
}


/* ================================================================
 * UNIFIED .abis-* SHELL COMPONENTS
 * One sidebar, one shell, one topbar, one stat card, one table —
 * shared, data-driven markup so every dashboard is structurally
 * identical. Styling lives in components.css + colors-system.css.
 * ================================================================ */

/**
 * Universal dashboard shell — open. Renders the mobile top bar, the
 * drawer backdrop, the sidebar, and opens <main>. Call abisShellEnd() to close.
 *
 * @param array  $nav    Ordered nav items. Each: ['key','label','href','icon'(fa class)].
 * @param string $active Key of the active item.
 * @param array  $user   ['name','role','initials'].
 * @param string $brand  Short product label shown by the logo (default 'ABIS').
 */
function abisShellStart(array $nav, string $active, array $user, string $brand = 'ABIS') {
    $logo = file_exists(__DIR__ . '/../images/brand/abis.png')
        ? '<img src="images/brand/abis.png" alt="ABIS">'
        : '<span class="abis-sidebar-mark">AB</span>';
    $initials = htmlspecialchars($user['initials'] ?? 'AB');
    $name     = htmlspecialchars($user['name'] ?? 'ABIS User');
    $role     = htmlspecialchars($user['role'] ?? 'Portal');

    echo '<div class="abis-shell">';

    /* Mobile sticky bar (hidden on desktop via CSS) */
    echo <<<HTML
    <div class="abis-mobile-bar">
        <div class="abis-mobile-brand">{$logo}<span>{$brand}</span></div>
        <button class="abis-hamburger" type="button" aria-label="Open menu" aria-controls="abisSidebar" aria-expanded="false" onclick="abisToggleSidebar(true)">&#9776;</button>
    </div>
    <div class="abis-sidebar-backdrop" data-abis-backdrop onclick="abisToggleSidebar(false)"></div>
HTML;

    /* Sidebar */
    echo '<aside class="abis-sidebar" id="abisSidebar" aria-label="Primary">';
    echo '<div class="abis-sidebar-logo">' . $logo . "<div><strong>{$brand}</strong><span>Workforce Portal</span></div></div>";
    echo '<nav class="abis-nav">';
    echo '<div class="abis-nav-label">Menu</div>';
    foreach ($nav as $item) {
        $isActive = ($item['key'] ?? '') === $active ? ' active' : '';
        $href  = htmlspecialchars($item['href'] ?? '#');
        $label = htmlspecialchars($item['label'] ?? '');
        $icon  = htmlspecialchars($item['icon'] ?? 'fa-solid fa-circle');
        $cur   = $isActive ? ' aria-current="page"' : '';
        echo "<a class=\"abis-nav-link{$isActive}\" href=\"{$href}\"{$cur}><span class=\"abis-nav-icon\"><i class=\"{$icon}\"></i></span><span>{$label}</span></a>";
    }
    echo '</nav>';
    echo <<<HTML
    <div class="abis-sidebar-user">
        <span class="abis-avatar">{$initials}</span>
        <div><strong>{$name}</strong><span>{$role}</span></div>
    </div>
HTML;
    echo '</aside>';

    echo '<main class="abis-main">';
}

function abisShellEnd() {
    echo '</main>';   // .abis-main
    echo '</div>';    // .abis-shell
}

/**
 * Universal top bar: breadcrumb + title + subtitle on the left, CTAs on the right.
 * @param string $title
 * @param string $subtitle
 * @param array  $crumbs  Ordered ['label'=>..,'href'=>..] (href optional on last).
 * @param string $actions Pre-rendered button HTML for the right side.
 */
function abisTopbar(string $title, string $subtitle = '', array $crumbs = [], string $actions = '') {
    echo '<header class="abis-topbar">';
    echo '<div class="abis-topbar-titles">';
    if (!empty($crumbs)) {
        echo '<nav class="abis-breadcrumb" aria-label="Breadcrumb">';
        $last = count($crumbs) - 1;
        foreach ($crumbs as $i => $c) {
            $label = htmlspecialchars($c['label'] ?? '');
            if ($i < $last && !empty($c['href'])) {
                echo '<a href="' . htmlspecialchars($c['href']) . '">' . $label . '</a><span>/</span>';
            } else {
                echo '<span>' . $label . '</span>';
            }
        }
        echo '</nav>';
    }
    echo '<h1>' . htmlspecialchars($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p>' . htmlspecialchars($subtitle) . '</p>';
    }
    echo '</div>';
    if ($actions !== '') {
        echo '<div class="abis-topbar-actions">' . $actions . '</div>';
    }
    echo '</header>';
}

/**
 * Universal stat card. Numeric values carry data-value for the counter animation.
 * @param string      $label
 * @param int|float|string $value  Numeric for animation; strings render as-is.
 * @param string      $trend   Optional trend text (e.g. '+12% this month').
 * @param string      $dir     'up' | 'down' | '' (controls color + arrow).
 * @param string      $icon    Optional fa icon class for the label row.
 */
function abisStatCard(string $label, $value, string $trend = '', string $dir = '', string $icon = '') {
    $isNumeric = is_numeric(str_replace([',', ' '], '', (string)$value));
    $dataAttr  = $isNumeric ? ' data-value="' . htmlspecialchars(str_replace([',', ' '], '', (string)$value)) . '"' : '';
    $iconHtml  = $icon ? '<i class="' . htmlspecialchars($icon) . '"></i>' : '';
    echo '<article class="abis-stat-card abis-reveal">';
    echo '<span class="abis-stat-label">' . $iconHtml . htmlspecialchars($label) . '</span>';
    echo '<span class="abis-stat-value"' . $dataAttr . '>' . htmlspecialchars((string)$value) . '</span>';
    if ($trend !== '') {
        $arrow = $dir === 'up' ? '&#9650;' : ($dir === 'down' ? '&#9660;' : '');
        echo '<span class="abis-stat-trend ' . htmlspecialchars($dir) . '">' . $arrow . ' ' . htmlspecialchars($trend) . '</span>';
    }
    echo '</article>';
}

/**
 * Semantic status badge. $tone maps to the shared status palette.
 * @param string $text
 * @param string $tone active|complete|pending|warning|rejected|danger|submitted|info|draft|neutral
 */
function abisBadge(string $text, string $tone = 'neutral') {
    $tone = strtolower($tone);
    echo '<span class="abis-badge is-' . htmlspecialchars($tone) . '">' . htmlspecialchars($text) . '</span>';
}

/** Scrollable, sticky-first-column table wrapper — open. */
function abisTableStart(array $columns) {
    echo '<div class="abis-table-wrap"><table class="abis-table"><thead><tr>';
    foreach ($columns as $col) {
        echo '<th>' . htmlspecialchars($col) . '</th>';
    }
    echo '</tr></thead><tbody>';
}

function abisTableEnd() {
    echo '</tbody></table></div>';
}

/**
 * One JS block powering the unified shell: drawer (tap/Escape/backdrop),
 * stat-card counters, scroll-triggered progress bars, table row stagger.
 * Include once per dashboard, before </body>.
 */
function abisShellScript() {
    echo <<<'HTML'
<script>
(function () {
  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!REDUCED) document.documentElement.classList.add('js-anim');
  window.abisToggleSidebar = function (open, instant) {
    var sb = document.getElementById('abisSidebar');
    var bd = document.querySelector('[data-abis-backdrop]');
    var btn = document.querySelector('.abis-hamburger');
    if (!sb || !bd) return;
    if (instant) { sb.classList.add('no-anim'); bd.classList.add('no-anim'); }
    sb.classList.toggle('open', open);
    bd.classList.toggle('open', open);
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (instant) {
      requestAnimationFrame(function () {
        sb.classList.remove('no-anim'); bd.classList.remove('no-anim');
      });
    }
  };
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') window.abisToggleSidebar(false, true); // instant on shortcut
  });

  /* ---- Counters ---- */
  function animateCounter(el) {
    var target = parseFloat(el.dataset.value);
    if (isNaN(target)) return;
    if (REDUCED) { el.textContent = target.toLocaleString(); return; }
    var duration = 800, start = performance.now();
    (function update(t) {
      var p = Math.min((t - start) / duration, 1);
      var ease = 1 - Math.pow(1 - p, 4);
      el.textContent = Math.round(target * ease).toLocaleString();
      if (p < 1) requestAnimationFrame(update);
    })(start);
  }

  /* ---- Progress bars ---- */
  function fillProgress(el) {
    el.style.width = (el.dataset.progress || 0) + '%';
  }

  var io = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      var el = entry.target;
      if (el.matches('.abis-stat-value[data-value]')) animateCounter(el);
      if (el.matches('.abis-progress > span[data-progress]')) fillProgress(el);
      io.unobserve(el);
    });
  }, { threshold: 0.4 }) : null;

  function bind() {
    var counters = document.querySelectorAll('.abis-stat-value[data-value]');
    var bars = document.querySelectorAll('.abis-progress > span[data-progress]');
    if (io) {
      counters.forEach(function (el) { io.observe(el); });
      bars.forEach(function (el) { io.observe(el); });
    } else {
      counters.forEach(animateCounter);
      bars.forEach(fillProgress);
    }
  }
  if (document.readyState !== 'loading') bind();
  else document.addEventListener('DOMContentLoaded', bind);
})();
</script>
HTML;
}

?>
