<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_auth('prescriber','admin');

$user = current_user();
$uid  = current_user_id();

// Stats
$pending    = Database::fetchOne("SELECT COUNT(*) c FROM consultations WHERE status='submitted'")['c'];
$approved   = Database::fetchOne("SELECT COUNT(*) c FROM consultations WHERE status='approved' AND reviewed_by=?",[$uid])['c'];
$rejected   = Database::fetchOne("SELECT COUNT(*) c FROM consultations WHERE status='rejected' AND reviewed_by=?",[$uid])['c'];
$rxIssued   = Database::fetchOne("SELECT COUNT(*) c FROM prescriptions WHERE prescriber_id=?",[$uid])['c'];

// Pending consultations queue
$queue = Database::fetchAll(
    "SELECT c.*, cn.name cond_name, CONCAT(u.first_name,' ',u.last_name) patient_name, u.date_of_birth, u.gender
     FROM consultations c
     JOIN conditions cn ON cn.id=c.condition_id
     JOIN users u ON u.id=c.patient_id
     WHERE c.status='submitted'
     ORDER BY c.submitted_at ASC LIMIT 20"
);

// Recently reviewed
$recent = Database::fetchAll(
    "SELECT c.*, cn.name cond_name, CONCAT(u.first_name,' ',u.last_name) patient_name
     FROM consultations c
     JOIN conditions cn ON cn.id=c.condition_id
     JOIN users u ON u.id=c.patient_id
     WHERE c.reviewed_by=? AND c.status IN ('approved','rejected')
     ORDER BY c.reviewed_at DESC LIMIT 10", [$uid]
);

$page_title = 'Prescriber Dashboard';
include APP_PATH . '/includes/header.php';
?>

<div class="pco-dash">
<div class="grid-container">
<div class="grid-x grid-margin-x">

  <?php include APP_PATH . '/includes/prescriber-sidebar.php'; ?>

  <!-- Main -->
  <div class="cell large-9 medium-8">

    <div style="margin-bottom:1.75rem;">
      <h1 style="font-size:1.55rem;margin-bottom:.2rem;">Prescriber Dashboard</h1>
      <p style="color:var(--pco-grey-500);font-size:.875rem;">
        Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?>,
        <?= e($user['first_name']) ?>. <?= $pending > 0 ? "<strong style='color:var(--pco-amber);'>$pending consultation".($pending!=1?'s':'')." awaiting your review.</strong>" : 'No pending consultations.' ?>
      </p>
    </div>

    <!-- Stats -->
    <div class="grid-x grid-margin-x" style="margin-bottom:1.5rem;">
      <?php foreach ([
        ['inbox','amber', $pending,  'Awaiting Review'],
        ['check','green', $approved, 'Approved Today'],
        ['xmark','red',   $rejected, 'Rejected'],
        ['file-prescription','purple',$rxIssued,'Prescriptions Issued'],
      ] as [$ico,$mod,$val,$lbl]): ?>
      <div class="cell large-3 medium-6 small-6" style="margin-bottom:1rem;">
        <div class="pco-stat">
          <div class="pco-stat__icon pco-stat__icon--<?= $mod ?>"><i class="fa-solid fa-<?= $ico ?>"></i></div>
          <div>
            <div class="pco-stat__val"><?= $val ?></div>
            <div class="pco-stat__label"><?= $lbl ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pending queue -->
    <div class="pco-card" style="margin-bottom:1.5rem;">
      <div class="pco-card__head">
        <h3><i class="fa-solid fa-inbox" style="color:var(--pco-amber);margin-right:.4rem;"></i>Pending Consultations</h3>
        <a href="<?= APP_URL ?>/pages/prescriber/queue.php" style="font-size:.8rem;color:var(--pco-purple);">View all</a>
      </div>
      <?php if (empty($queue)): ?>
      <div class="pco-card__body text-center" style="padding:2.5rem;">
        <i class="fa-solid fa-circle-check" style="font-size:2rem;color:var(--pco-green);display:block;margin-bottom:.75rem;"></i>
        <p style="color:var(--pco-grey-500);margin:0;">All consultations reviewed — queue is clear.</p>
      </div>
      <?php else: ?>
      <div class="pco-table-wrap">
        <table class="pco-table">
          <thead>
            <tr><th>Patient</th><th>Condition</th><th>Age / Sex</th><th>Submitted</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($queue as $c):
              $age = $c['date_of_birth'] ? (new DateTime())->diff(new DateTime($c['date_of_birth']))->y : '—';
            ?>
            <tr>
              <td data-label="Patient"><strong><?= e($c['patient_name']) ?></strong></td>
              <td data-label="Condition"><?= e($c['cond_name']) ?></td>
              <td data-label="Age/Sex"><?= $age ?> / <?= e(ucfirst($c['gender']??'—')) ?></td>
              <td data-label="Submitted"><?= $c['submitted_at'] ? time_ago($c['submitted_at']) : '—' ?></td>
              <td>
                <a href="<?= APP_URL ?>/pages/prescriber/review.php?id=<?= $c['id'] ?>" class="pco-btn pco-btn--primary pco-btn--sm">
                  Review <i class="fa-solid fa-arrow-right fa-xs"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Recently reviewed -->
    <?php if (!empty($recent)): ?>
    <div class="pco-card">
      <div class="pco-card__head">
        <h3><i class="fa-solid fa-clock-rotate-left" style="color:var(--pco-purple);margin-right:.4rem;"></i>Recently Reviewed</h3>
      </div>
      <div class="pco-table-wrap">
        <table class="pco-table">
          <thead><tr><th>Patient</th><th>Condition</th><th>Decision</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($recent as $c): ?>
            <tr>
              <td data-label="Patient"><?= e($c['patient_name']) ?></td>
              <td data-label="Condition"><?= e($c['cond_name']) ?></td>
              <td data-label="Decision"><?= status_badge($c['status']) ?></td>
              <td data-label="Date"><?= $c['reviewed_at'] ? date('d M Y',strtotime($c['reviewed_at'])) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
</div>
</div>

<?php include APP_PATH . '/includes/footer.php'; ?>
