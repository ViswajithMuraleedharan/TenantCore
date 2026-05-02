<?php
require_once __DIR__ . '/helpers.php';
$title      = 'Team';
$activePage = 'team';

ob_start(); ?>

<div class="page-header">
  <div>
    <div class="page-title">Team</div>
    <div class="page-subtitle">Manage members and their roles.</div>
  </div>
  <button class="btn btn-primary" onclick="document.getElementById('invite-modal').style.display='flex'">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Invite Member
  </button>
</div>

<div class="section">
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Member</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr>
        </thead>
        <tbody>
          <?php
          $members = [
            ['Jane Smith',    'jane@example.com',  'admin',  'active',  'Jan 1, 2024'],
            ['Bob Johnson',   'bob@example.com',   'member', 'active',  'Feb 12, 2024'],
            ['Carol White',   'carol@example.com', 'member', 'pending', 'Mar 5, 2024'],
            ['Dave Brown',    'dave@example.com',  'viewer', 'active',  'Apr 20, 2024'],
          ];
          $roleBadge   = ['admin'=>'badge-blue','member'=>'badge-green','viewer'=>'badge-gray'];
          $statusBadge = ['active'=>'badge-green','pending'=>'badge-yellow'];
          foreach ($members as $m):
            $initials = strtoupper(substr($m[0],0,1) . (strpos($m[0],' ') ? substr($m[0], strpos($m[0],' ')+1, 1) : ''));
          ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div class="avatar" style="width:32px;height:32px;font-size:12px"><?= e($initials) ?></div>
                  <div>
                    <div style="font-weight:600"><?= e($m[0]) ?></div>
                    <div style="font-size:12px;color:var(--muted)"><?= e($m[1]) ?></div>
                  </div>
                </div>
              </td>
              <td><span class="badge <?= $roleBadge[$m[2]] ?>"><?= e(ucfirst($m[2])) ?></span></td>
              <td><span class="badge <?= $statusBadge[$m[3]] ?>"><?= e(ucfirst($m[3])) ?></span></td>
              <td style="color:var(--muted)"><?= e($m[4]) ?></td>
              <td>
                <div style="display:flex;gap:6px;justify-content:flex-end">
                  <button class="btn btn-outline btn-sm">Edit</button>
                  <button class="btn btn-danger btn-sm">Remove</button>
                </div>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

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
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Send Invitation</button>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/app.php';
