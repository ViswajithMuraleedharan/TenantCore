<?php
require_once __DIR__ . '/helpers.php';
requireAuth();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require_once __DIR__ . '/../config/database.php';

use Illuminate\Database\Capsule\Manager as DB;

$payload  = json_decode(base64_decode(strtr(explode('.', $_SESSION['access_token'])[1], '-_', '+/')), true);
$tenantId = $payload['tenant_id'];
$myRole   = $payload['role'];

$members = DB::table('tenant_users')
    ->join('users', 'users.id', '=', 'tenant_users.user_id')
    ->where('tenant_users.tenant_id', $tenantId)
    ->select(['users.id', 'users.name', 'users.email', 'tenant_users.role', 'tenant_users.joined_at'])
    ->get();

$title      = 'Team';
$activePage = 'team';

$roleBadge   = ['owner'=>'badge-blue','admin'=>'badge-blue','member'=>'badge-green','viewer'=>'badge-gray'];
$canInvite   = in_array($myRole, ['owner', 'admin']);

ob_start(); ?>

<div class="page-header">
  <div>
    <div class="page-title">Team</div>
    <div class="page-subtitle">Manage members and their roles.</div>
  </div>
  <?php if ($canInvite): ?>
    <button class="btn btn-primary" onclick="document.getElementById('invite-modal').style.display='flex'">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Invite Member
    </button>
  <?php endif ?>
</div>

<div class="section">
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Member</th><th>Role</th><th>Joined</th><?= $canInvite ? '<th></th>' : '' ?></tr>
        </thead>
        <tbody>
          <?php if ($members->isEmpty()): ?>
            <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:24px">No members yet</td></tr>
          <?php else: ?>
            <?php foreach ($members as $m):
              $initials = strtoupper(substr($m->name ?? $m->email, 0, 1) . (strpos($m->name ?? '', ' ') ? substr($m->name, strpos($m->name,' ')+1, 1) : ''));
            ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px">
                    <div class="avatar" style="width:32px;height:32px;font-size:12px"><?= e($initials) ?></div>
                    <div>
                      <div style="font-weight:600"><?= e($m->name ?? '—') ?></div>
                      <div style="font-size:12px;color:var(--muted)"><?= e($m->email) ?></div>
                    </div>
                  </div>
                </td>
                <td><span class="badge <?= $roleBadge[$m->role] ?? 'badge-gray' ?>"><?= e(ucfirst($m->role)) ?></span></td>
                <td style="color:var(--muted)"><?= e($m->joined_at ? date('M j, Y', strtotime($m->joined_at)) : '—') ?></td>
                <?php if ($canInvite): ?>
                  <td>
                    <form method="POST" action="/remove-member.php" style="display:inline">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= e($m->id) ?>">
                      <button type="submit" class="btn btn-danger btn-sm"
                        onclick="return confirm('Remove this member?')"
                        <?= $m->role === 'owner' ? 'disabled' : '' ?>>Remove</button>
                    </form>
                  </td>
                <?php endif ?>
              </tr>
            <?php endforeach ?>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($canInvite): ?>
<!-- Invite Modal -->
<div id="invite-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center">
  <div class="card" style="width:100%;max-width:420px;position:relative">
    <button onclick="document.getElementById('invite-modal').style.display='none'"
      style="position:absolute;top:14px;right:14px;background:none;border:none;font-size:18px;color:var(--muted);cursor:pointer">✕</button>
    <div class="section-title" style="margin-bottom:20px">Invite Team Member</div>
    <form method="POST" action="/invite.php">
      <?= csrfField() ?>
      <div class="form-group">
        <label>Email address</label>
        <input type="email" name="email" placeholder="colleague@example.com" required>
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role">
          <option value="member">Member</option>
          <option value="admin">Admin</option>
          <option value="viewer">Viewer</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Add Member</button>
    </form>
  </div>
</div>
<?php endif ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/app.php';
