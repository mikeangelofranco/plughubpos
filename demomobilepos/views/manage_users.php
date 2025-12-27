<?php
declare(strict_types=1);

ob_start();
$flash = is_string($flash ?? null) ? $flash : null;
$flashError = is_string($flash_error ?? null) ? $flash_error : null;
$users = is_array($users ?? null) ? $users : [];
$tenantName = is_string($tenant_name ?? null) ? $tenant_name : 'Tenant';
$query = is_string($query ?? null) ? $query : '';
$csrf = Csrf::token();
$roles = ['Manager', 'Cashier', 'Readonly'];
?>
<header class="topbar">
  <div class="topbar-row">
    <div class="brand-text">
      <div class="brand-name">Manage Users</div>
      <div class="brand-sub">Tenant: <?= e($tenantName) ?></div>
    </div>
    <div class="top-actions">
      <a class="btn btn-ghost" href="/">← Back</a>
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
    <h2>Add User</h2>
    <form method="post" class="form">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
      <input type="hidden" name="action" value="add_user" />
      <label class="label">
        <span>Username</span>
        <input class="input" name="username" required />
      </label>
      <label class="label">
        <span>Password</span>
        <input class="input" type="password" name="password" required />
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
      <button class="btn btn-primary" type="submit">Create User</button>
    </form>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h2>Users</h2>
      <form class="table-search" method="get">
        <input class="input input-compact" name="q" value="<?= e($query) ?>" placeholder="Search users..." />
      </form>
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
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$users): ?>
            <tr data-empty-users><td colspan="5">No users found.</td></tr>
          <?php else: ?>
            <?php foreach ($users as $u): ?>
              <tr data-user-row data-search="<?= e(strtolower((string) ($u['username'] ?? '') . ' ' . ($u['full_name'] ?? '') . ' ' . ($u['contact_number'] ?? ''))) ?>">
                <td>
                  <div class="strong"><?= e((string) ($u['username'] ?? '')) ?></div>
                  <div class="table-meta">ID: <?= (int) ($u['id'] ?? 0) ?></div>
                </td>
                <td><?= e((string) ($u['full_name'] ?? '')) ?></td>
                <td><?= e((string) ($u['contact_number'] ?? '')) ?></td>
                <td><span class="role-chip role-<?= e(strtolower((string) ($u['role'] ?? ''))) ?>"><?= e((string) ($u['role'] ?? '')) ?></span></td>
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
                        <option value="<?= e($r) ?>" <?= strtolower((string) ($u['role'] ?? '')) === strtolower($r) ? 'selected' : '' ?>><?= e($r) ?></option>
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
</main>

<?php
$content = ob_get_clean();
$script = <<<'HTML'
<script>
(function(){
  const qUsers = document.querySelector('[name="q"]');
  const userRows = Array.from(document.querySelectorAll('[data-user-row]'));
  const emptyUsers = document.querySelector('[data-empty-users]');
  const filter = () => {
    const q = (qUsers?.value || '').toLowerCase();
    let shown = 0;
    userRows.forEach((row) => {
      const hay = (row.dataset.search || '').toLowerCase();
      const match = !q || hay.includes(q);
      row.style.display = match ? '' : 'none';
      if (match) shown += 1;
    });
    if (emptyUsers) {
      emptyUsers.style.display = shown === 0 ? '' : 'none';
    }
  };
  qUsers?.addEventListener('input', filter);
  filter();
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
