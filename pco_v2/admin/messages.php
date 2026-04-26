<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('admin');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = clean($_POST['action'] ?? '');
    $msgId  = (int)($_POST['msg_id'] ?? 0);
    if ($action === 'mark_read')   { Database::update('contact_messages',['is_read'=>1],['id'=>$msgId]); }
    if ($action === 'mark_unread') { Database::update('contact_messages',['is_read'=>0],['id'=>$msgId]); }
    if ($action === 'delete')      { Database::query("DELETE FROM contact_messages WHERE id=?",[$msgId]); flash_set('success','Message deleted.'); }
    redirect('/pages/admin/messages.php' . ($action!=='delete'?'?view='.$msgId:''));
}

$viewId  = (int)($_GET['view'] ?? 0);
$viewMsg = $viewId ? Database::fetchOne("SELECT * FROM contact_messages WHERE id=?",[$viewId]) : null;
if ($viewMsg && !$viewMsg['is_read']) { Database::update('contact_messages',['is_read'=>1],['id'=>$viewId]); $viewMsg['is_read']=1; }

$unread   = Database::fetchOne("SELECT COUNT(*) c FROM contact_messages WHERE is_read=0")['c'];
$page     = max(1,(int)($_GET['page']??1));
$per      = 20;
$total    = Database::fetchOne("SELECT COUNT(*) c FROM contact_messages")['c'];
$pg       = paginate($total,$page,$per);
$messages = Database::fetchAll("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT {$per} OFFSET {$pg['offset']}");

$page_title = 'Messages';
include APP_PATH . '/includes/header.php';
?>
<div class="pco-dash"><div class="grid-container"><div class="grid-x grid-margin-x">
<?php include APP_PATH . '/includes/admin-sidebar.php'; ?>
<div class="cell large-9 medium-8">

  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <h1 style="font-size:1.55rem;margin:0;">Contact Messages <?php if ($unread): ?><span class="pco-badge badge--amber"><?= $unread ?> unread</span><?php endif; ?></h1>
  </div>

  <?= flash_render() ?>

  <div class="grid-x grid-margin-x">
    <!-- Message list -->
    <div class="cell large-4" style="margin-bottom:1rem;">
      <div class="pco-card" style="overflow:hidden;">
        <?php if (empty($messages)): ?>
        <div class="pco-card__body text-center" style="padding:2rem;">
          <p style="color:var(--pco-grey-500);margin:0;">No messages yet.</p>
        </div>
        <?php else: ?>
        <?php foreach ($messages as $m): ?>
        <a href="?view=<?= $m['id'] ?>" style="display:block;padding:.9rem 1rem;border-bottom:1px solid var(--pco-grey-100);text-decoration:none;transition:background var(--pco-t);<?= $viewId===$m['id']?'background:var(--pco-lavender-tint);border-left:3px solid var(--pco-purple);':'' ?><?= !$m['is_read']?'background:rgba(196,168,224,.08);':'' ?>">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;">
            <strong style="font-size:.875rem;color:var(--pco-black);"><?= e($m['name']) ?></strong>
            <?php if (!$m['is_read']): ?><span style="width:8px;height:8px;background:var(--pco-purple);border-radius:50%;flex-shrink:0;margin-top:.3rem;"></span><?php endif; ?>
          </div>
          <div style="font-size:.78rem;color:var(--pco-grey-500);"><?= e($m['subject']) ?></div>
          <div style="font-size:.75rem;color:var(--pco-grey-400);margin-top:.2rem;"><?= time_ago($m['created_at']) ?></div>
        </a>
        <?php endforeach; ?>
        <?php if ($pg['pages']>1): ?>
        <div style="padding:.6rem 1rem;display:flex;gap:.3rem;">
          <?php if ($pg['has_prev']): ?><a href="?page=<?= $page-1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">← Prev</a><?php endif; ?>
          <?php if ($pg['has_next']): ?><a href="?page=<?= $page+1 ?>" class="pco-btn pco-btn--ghost pco-btn--sm">Next →</a><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Message detail -->
    <div class="cell large-8">
      <?php if ($viewMsg): ?>
      <div class="pco-card">
        <div class="pco-card__head">
          <div>
            <div style="font-weight:700;"><?= e($viewMsg['subject']) ?></div>
            <div style="font-size:.8rem;color:var(--pco-grey-500);">From <?= e($viewMsg['name']) ?> &lt;<?= e($viewMsg['email']) ?>&gt; · <?= date('d M Y H:i',strtotime($viewMsg['created_at'])) ?></div>
          </div>
          <div style="display:flex;gap:.3rem;">
            <form method="POST" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="msg_id" value="<?= $viewMsg['id'] ?>">
              <input type="hidden" name="action" value="<?= $viewMsg['is_read']?'mark_unread':'mark_read' ?>">
              <button class="pco-btn pco-btn--ghost pco-btn--sm"><?= $viewMsg['is_read']?'Mark Unread':'Mark Read' ?></button>
            </form>
            <form method="POST" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="msg_id" value="<?= $viewMsg['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button class="pco-btn pco-btn--danger pco-btn--sm" data-confirm="Delete this message?"><i class="fa-solid fa-trash"></i></button>
            </form>
          </div>
        </div>
        <div class="pco-card__body">
          <p style="white-space:pre-wrap;font-size:.9rem;line-height:1.7;"><?= e($viewMsg['message']) ?></p>
          <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--pco-grey-200);font-size:.78rem;color:var(--pco-grey-500);">
            IP: <?= e($viewMsg['ip_address']) ?>
          </div>
        </div>
      </div>
      <?php else: ?>
      <div class="pco-card"><div class="pco-card__body text-center" style="padding:3rem;">
        <i class="fa-solid fa-envelope-open" style="font-size:2rem;color:var(--pco-grey-300);display:block;margin-bottom:.75rem;"></i>
        <p style="color:var(--pco-grey-500);margin:0;">Select a message to read</p>
      </div></div>
      <?php endif; ?>
    </div>
  </div>

</div></div></div></div>
<?php include APP_PATH . '/includes/footer.php'; ?>
