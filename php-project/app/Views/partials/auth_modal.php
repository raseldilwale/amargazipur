<div class="modal fade" id="agAuthPromptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><?= e(__('auth_modal_title')) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-secondary">
        <?= e(__('auth_modal_desc')) ?>
      </div>
      <div class="modal-footer">
        <a href="<?= e(url('/login')) ?>" class="btn btn-outline-dark"><?= e(__('login')) ?></a>
        <a href="<?= e(url('/register')) ?>" class="btn ag-btn-primary"><?= e(__('register')) ?></a>
      </div>
    </div>
  </div>
</div>
