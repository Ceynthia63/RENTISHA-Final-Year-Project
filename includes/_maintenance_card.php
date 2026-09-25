<?php
/**
 * Rentisha RMS — Shared maintenance card partial.
 * Expects $req array (row from getMaintenanceRequests).
 * Used in caretaker/maintenance.php tabs.
 */
?>
<div class="maintenance-card">
  <div class="mc-header">
    <div>
      <span class="mc-title"><?= htmlspecialchars($req['title']) ?></span>
      <span class="badge badge-info" style="margin-left:6px;font-size:.7rem;"><?= htmlspecialchars($req['category']) ?></span>
      <?php if (in_array($req['priority'],['Urgent','Emergency'])): ?>
      <span class="badge badge-overdue" style="margin-left:4px;font-size:.7rem;"><?= $req['priority'] ?></span>
      <?php endif; ?>
    </div>
    <span class="badge <?= statusBadgeClass($req['status']) ?>"><?= $req['status'] ?></span>
  </div>
  <div class="mc-desc"><?= htmlspecialchars(mb_substr($req['description'],0,120)) ?>…</div>
  <div class="mc-meta">
    <span><i class="bi bi-person"></i> <?= htmlspecialchars($req['tenant_name']) ?></span>
    <span><i class="bi bi-door-open"></i> <?= htmlspecialchars($req['unit_number'] ?? '—') ?></span>
    <span><i class="bi bi-calendar3"></i> <?= formatDate($req['created_at']) ?></span>
  </div>
  <?php if ($req['caretaker_notes']): ?>
  <div style="margin-top:8px;padding:8px 12px;background:var(--primary-pale);border-radius:6px;font-size:.8rem;">
    <i class="bi bi-pencil-square" style="color:var(--primary);"></i>
    <strong>Note:</strong> <?= htmlspecialchars($req['caretaker_notes']) ?>
  </div>
  <?php endif; ?>
  <div class="d-flex gap-2 mt-2" style="flex-wrap:wrap;">
    <?php if ($req['status'] === 'Pending'): ?>
    <form method="POST" action="../includes/save_maintenance.php" style="display:inline;">
      <input type="hidden" name="edit_id"    value="<?= $req['id'] ?>">
      <input type="hidden" name="new_status" value="In Progress">
      <input type="hidden" name="notes"      value="Work started.">
      <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-play-circle"></i> Start Work</button>
    </form>
    <?php elseif ($req['status'] === 'In Progress'): ?>
    <form method="POST" action="../includes/save_maintenance.php" style="display:inline;">
      <input type="hidden" name="edit_id"    value="<?= $req['id'] ?>">
      <input type="hidden" name="new_status" value="Resolved">
      <input type="hidden" name="notes"      value="Issue resolved.">
      <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-circle"></i> Mark Resolved</button>
    </form>
    <?php endif; ?>
    <button class="btn btn-outline btn-sm" onclick="openUpdateModal(<?= htmlspecialchars(json_encode($req)) ?>)">
      <i class="bi bi-pencil"></i> Add Note / Update
    </button>
  </div>
</div>
