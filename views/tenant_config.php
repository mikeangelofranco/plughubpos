<?php
declare(strict_types=1);

ob_start();
$flash = is_string($flash ?? null) ? $flash : null;
$flashError = is_string($flash_error ?? null) ? $flash_error : null;
$tenants = is_array($tenants ?? null) ? $tenants : [];
$users = is_array($users ?? null) ? $users : [];
$csrf = Csrf::token();
$roles = ['Admin', 'Manager', 'Cashier', 'Readonly'];
$tenantQuery = is_string($tenant_q ?? null) ? $tenant_q : '';
$userQuery = is_string($user_q ?? null) ? $user_q : '';

$userCounts = [];
foreach ($users as $u) {
    $tid = $u['tenant_id'] ?? null;
    if ($tid === null) {
        continue;
    }
    $tid = (int) $tid;
    $userCounts[$tid] = ($userCounts[$tid] ?? 0) + 1;
}
?>
<header class="topbar">
  <div class="topbar-row">
    <div class="brand-text">
      <div class="brand-name">Tenant Configuration</div>
      <div class="brand-sub">Admin-only · Manage tenants and members</div>
    </div>
    <div class="top-actions">
      <a class="btn btn-ghost" href="/">← Back to POS</a>
      <a class="btn btn-primary" href="/logout">Logout</a>
    </div>
  </div>
</header>

<main class="content">
  <?php if ($flash): ?>
    <section class="panel"><div class="notice notice-ok"><?= e($flash) ?></div></section>
  <?php endif; ?>
  <?php if ($flashError): ?>
    <section class="panel"><div class="notice notice-error"><?= e($flashError) ?></div></section>
  <?php endif; ?>

  <section class="panel">
    <h2>Create Tenant</h2>
    <form method="post" class="form">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
      <input type="hidden" name="action" value="add_tenant" />
      <label class="label">
        <span>Tenant name</span>
        <input class="input" name="name" placeholder="e.g., Lagos Storefront" required />
      </label>
      <label class="label">
        <span>Slug (optional)</span>
        <input class="input" name="slug" placeholder="auto-generated if empty" />
      </label>
      <label class="label">
        <span>Address</span>
        <input class="input" name="address" placeholder="Street, City" />
      </label>
      <label class="label">
        <span>Contact number</span>
        <input class="input" name="contact_number" placeholder="+234..." />
      </label>
      <button class="btn btn-primary" type="submit">Create Tenant</button>
    </form>
  </section>

  <section class="panel">
    <h2>Add User to Tenant</h2>
    <form method="post" class="form">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
      <input type="hidden" name="action" value="add_user" />
      <label class="label">
        <span>Username</span>
        <input class="input" name="username" autocomplete="off" required />
      </label>
      <label class="label">
        <span>Password</span>
        <input class="input" type="password" name="password" autocomplete="new-password" required />
      </label>
      <label class="label">
        <span>Full name</span>
        <input class="input" name="full_name" />
      </label>
      <label class="label">
        <span>Contact number</span>
        <input class="input" name="contact_number" />
      </label>
      <label class="label">
        <span>Role</span>
        <select class="input" name="role" required>
          <?php foreach ($roles as $r): ?>
            <option value="<?= e($r) ?>"><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="label">
        <span>Tenant (required for non-admin roles)</span>
        <select class="input" name="tenant_id">
          <option value="">System (Admins only)</option>
          <?php foreach ($tenants as $t): ?>
            <option value="<?= (int) ($t['id'] ?? 0) ?>"><?= e((string) ($t['name'] ?? 'Tenant')) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn btn-primary" type="submit">Create User</button>
    </form>
  </section>

  <div class="admin-grid">
    <section class="panel">
      <div class="panel-head">
        <h2>Tenants</h2>
        <div class="table-search">
          <input class="input input-compact" name="tenant_q" value="<?= e($tenantQuery) ?>" placeholder="Search tenants..." data-search-tenants />
        </div>
      </div>
      <button class="accordion-toggle" type="button" data-accordion-toggle="tenants">Toggle Tenants</button>
      <div class="accordion-content is-collapsed" data-accordion-content="tenants">
        <div class="table-wrap">
          <table class="data-table" data-table-tenants>
            <thead>
              <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Address</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Users</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$tenants): ?>
              <tr data-empty-tenants><td colspan="7">No tenants found.</td></tr>
              <?php else: ?>
              <?php foreach ($tenants as $t): ?>
                <?php
                  $active = (bool) ($t['active'] ?? true);
                  $tid = (int) ($t['id'] ?? 0);
                  $count = $userCounts[$tid] ?? 0;
                ?>
                <tr data-tenant-row data-search="<?= e(strtolower((string) ($t['name'] ?? '') . ' ' . ($t['address'] ?? '') . ' ' . ($t['contact_number'] ?? ''))) ?>">
                  <td>
                    <div class="strong"><?= e((string) ($t['name'] ?? 'Tenant')) ?></div>
                    <div class="table-meta">ID: <?= $tid ?></div>
                  </td>
                  <td><?= e((string) ($t['slug'] ?? '')) ?></td>
                  <td><?= e((string) ($t['address'] ?? '')) ?></td>
                  <td><?= e((string) ($t['contact_number'] ?? '')) ?></td>
                  <td>
                    <span class="badge <?= $active ? 'badge-success' : 'badge-soft' ?>">
                      <?= $active ? 'Active' : 'Inactive' ?>
                    </span>
                  </td>
                  <td><?= $count ?></td>
                  <td>
                    <form method="post" class="inline-form">
                      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                      <input type="hidden" name="action" value="update_tenant" />
                      <input type="hidden" name="id" value="<?= $tid ?>" />
                      <input class="input input-compact" name="name" value="<?= e((string) ($t['name'] ?? '')) ?>" required />
                      <input class="input input-compact" name="slug" value="<?= e((string) ($t['slug'] ?? '')) ?>" />
                      <input class="input input-compact" name="address" value="<?= e((string) ($t['address'] ?? '')) ?>" placeholder="Address" />
                      <input class="input input-compact" name="contact_number" value="<?= e((string) ($t['contact_number'] ?? '')) ?>" placeholder="Contact" />
                      <button class="btn btn-primary btn-compact" type="submit">Save</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Users</h2>
        <div class="table-search">
          <input class="input input-compact" name="user_q" value="<?= e($userQuery) ?>" placeholder="Search users..." data-search-users />
        </div>
      </div>
      <button class="accordion-toggle" type="button" data-accordion-toggle="users">Toggle Users</button>
      <div class="accordion-content is-collapsed" data-accordion-content="users">
        <div class="table-wrap">
          <table class="data-table" data-table-users>
            <thead>
              <tr>
                <th>User</th>
                <th>Full name</th>
                <th>Contact</th>
              <th>Role</th>
              <th>Tenant</th>
              <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$users): ?>
              <tr data-empty-users><td colspan="7">No users found.</td></tr>
              <?php else: ?>
              <?php foreach ($users as $u): ?>
                <?php
                  $role = (string) ($u['role'] ?? '');
                  $tenantLabel = $u['tenant_name'] ?? null;
                  if (!is_string($tenantLabel) || $tenantLabel === '') {
                      $tenantLabel = 'System / All';
                  }
                  $active = (bool) ($u['active'] ?? true);
                ?>
                <tr data-user-row data-search="<?= e(strtolower((string) ($u['username'] ?? '') . ' ' . ($u['full_name'] ?? '') . ' ' . ($u['contact_number'] ?? '') . ' ' . ($tenantLabel ?? ''))) ?>">
                  <td>
                    <div class="strong"><?= e((string) ($u['username'] ?? '')) ?></div>
                    <div class="table-meta">ID: <?= (int) ($u['id'] ?? 0) ?></div>
                  </td>
                  <td><?= e((string) ($u['full_name'] ?? '')) ?></td>
                  <td><?= e((string) ($u['contact_number'] ?? '')) ?></td>
                  <td><span class="role-chip role-<?= e(strtolower($role)) ?>"><?= e($role) ?></span></td>
                  <td><?= e($tenantLabel) ?></td>
                  <td>
                    <span class="badge <?= $active ? 'badge-success' : 'badge-soft' ?>">
                      <?= $active ? 'Active' : 'Inactive' ?>
                    </span>
                  </td>
                  <td>
                    <form method="post" class="inline-form">
                      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                      <input type="hidden" name="action" value="update_user" />
                      <input type="hidden" name="id" value="<?= (int) ($u['id'] ?? 0) ?>" />
                      <input class="input input-compact" name="username" value="<?= e((string) ($u['username'] ?? '')) ?>" required />
                      <input class="input input-compact" name="full_name" value="<?= e((string) ($u['full_name'] ?? '')) ?>" placeholder="Full name" />
                      <input class="input input-compact" name="contact_number" value="<?= e((string) ($u['contact_number'] ?? '')) ?>" placeholder="Contact" />
                      <select class="input input-compact" name="role">
                        <?php foreach ($roles as $r): ?>
                          <option value="<?= e($r) ?>" <?= strtolower($role) === strtolower($r) ? 'selected' : '' ?>><?= e($r) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <select class="input input-compact" name="tenant_id">
                        <option value="">System (Admins only)</option>
                        <?php foreach ($tenants as $t): ?>
                          <?php $tidOpt = (int) ($t['id'] ?? 0); ?>
                          <option value="<?= $tidOpt ?>" <?= $tidOpt === (int) ($u['tenant_id'] ?? null) ? 'selected' : '' ?>><?= e((string) ($t['name'] ?? 'Tenant')) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <div class="inline-actions">
                        <button class="btn btn-primary btn-compact" type="submit">Save</button>
                      </div>
                    </form>
                    <form method="post" class="inline-form" onsubmit="return confirm('Delete this user?');">
                      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                      <input type="hidden" name="action" value="delete_user" />
                      <input type="hidden" name="id" value="<?= (int) ($u['id'] ?? 0) ?>" />
                      <button class="btn btn-danger btn-compact" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</main>

<?php
$content = ob_get_clean();
$script = <<<'HTML'
<script>
(function(){
  const qTenants = document.querySelector('[data-search-tenants]');
  const qUsers = document.querySelector('[data-search-users]');
  const tenantRows = Array.from(document.querySelectorAll('[data-tenant-row]'));
  const userRows = Array.from(document.querySelectorAll('[data-user-row]'));
  const emptyTenants = document.querySelector('[data-empty-tenants]');
  const emptyUsers = document.querySelector('[data-empty-users]');

  const filter = (input, rows, emptyRow) => {
    if (!input || !rows) return;
    const q = input.value.toLowerCase();
    let shown = 0;
    rows.forEach((row) => {
      const hay = (row.dataset.search || '').toLowerCase();
      const match = !q || hay.includes(q);
      row.style.display = match ? '' : 'none';
      if (match) shown += 1;
    });
    if (emptyRow) {
      emptyRow.style.display = shown === 0 ? '' : 'none';
    }
  };

  qTenants?.addEventListener('input', () => filter(qTenants, tenantRows, emptyTenants));
  qUsers?.addEventListener('input', () => filter(qUsers, userRows, emptyUsers));
  filter(qTenants, tenantRows, emptyTenants);
  filter(qUsers, userRows, emptyUsers);

  document.querySelectorAll('[data-accordion-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const key = btn.getAttribute('data-accordion-toggle');
      const content = document.querySelector(`[data-accordion-content="${key}"]`);
      if (!content) return;
      content.classList.toggle('is-collapsed');
    });
  });
})();
</script>
HTML;
$content .= $script;
require __DIR__ . '/layout.php';
