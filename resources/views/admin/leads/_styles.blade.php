<style>
  /* Lead pipeline — Bootstrap 5.1 theme, so no *-subtle utilities. */
  .lead-board { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 8px; align-items: flex-start; }
  .lead-col { flex: 0 0 272px; background: #f6f7f9; border-radius: 10px; display: flex; flex-direction: column; max-height: 74vh; }
  .lead-col.closed { flex-basis: 232px; opacity: .92; }
  .lead-col-head { padding: 10px 12px; display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13px; border-bottom: 1px solid #e9ebef; }
  .lead-col-body { padding: 8px; overflow-y: auto; min-height: 90px; flex: 1; border-radius: 0 0 10px 10px; transition: background .15s; }
  .lead-col-body.drop-over { background: rgba(15, 156, 243, .08); outline: 2px dashed rgba(15, 156, 243, .45); outline-offset: -4px; }
  .stage-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex: 0 0 9px; }
  .lead-card { background: #fff; border: 1px solid #e6e8ec; border-radius: 8px; padding: 10px 11px; margin-bottom: 8px; cursor: grab; transition: box-shadow .15s, transform .15s; display: block; color: inherit; }
  .lead-card:hover { box-shadow: 0 3px 10px rgba(20, 24, 36, .08); color: inherit; }
  .lead-card.dragging { opacity: .5; transform: rotate(1deg); }
  .lead-card .lead-name { font-weight: 600; font-size: 13.5px; color: #1f2430; }
  .lead-card .lead-meta { font-size: 12px; color: #6c757d; }
  .lead-card .lead-due { color: #d63939; font-weight: 600; }
  .lead-avatar { width: 24px; height: 24px; border-radius: 50%; background: #e9ecef; color: #495057; font-size: 10.5px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
  .lead-avatar.none { background: transparent; border: 1px dashed #adb5bd; color: #adb5bd; }
  .lead-chip { font-size: 11px; padding: 2px 7px; border-radius: 10px; background: #f1f3f5; color: #495057; white-space: nowrap; }
  .lead-empty { font-size: 12px; color: #adb5bd; text-align: center; padding: 18px 6px; }
  .timeline-item { position: relative; padding: 0 0 16px 34px; }
  .timeline-item::before { content: ''; position: absolute; left: 13px; top: 28px; bottom: 0; width: 2px; background: #eef0f3; }
  .timeline-item:last-child::before { display: none; }
  .timeline-icon { position: absolute; left: 0; top: 0; width: 28px; height: 28px; border-radius: 50%; background: #f1f3f5; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #495057; }
  .stage-steps .btn { border-radius: 20px; font-size: 12.5px; }
  .kind-pick .btn-check:checked + .btn { color: #fff; }
  @media (max-width: 575px) { .lead-col { flex-basis: 84vw; } }
</style>
