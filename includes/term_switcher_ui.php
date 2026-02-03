<?php
if (!function_exists('renderTermSwitcher')) {
    function renderTermSwitcher(array $context, array $options = []): void
    {
        static $assetsRendered = false;
        static $renderCount = 0;

        $renderCount++;
        $terms = $context['terms'] ?? [];
        $active = $context['active'] ?? null;
        $fallbackSemester = $options['fallback_semester'] ?? null;
        $schoolName = isset($options['school_name']) ? (string)$options['school_name'] : '';
        $isOverride = isset($context['override_id']) && $context['override_id'] !== null;

        $titleParts = [];
        if (!empty($active['semester_number'])) {
            $titleParts[] = 'Semester ' . $active['semester_number'];
        }
        if (!empty($active['semester_term'])) {
            $titleParts[] = ucfirst((string)$active['semester_term']) . ' Term';
        }
        if (!empty($active['academic_year'])) {
            $titleParts[] = 'AY ' . $active['academic_year'];
        }
        if (!$titleParts && $fallbackSemester !== null && $fallbackSemester !== '') {
            $titleParts[] = 'Semester ' . $fallbackSemester;
        }
        $termTitle = $titleParts ? implode(' • ', $titleParts) : 'Semester';

        $startDate = $active['start_date'] ?? null;
        $endDate = $active['end_date'] ?? null;
        $termDatesLabel = 'Calendar dates unavailable';
        if ($startDate && $endDate) {
            $termDatesLabel = date('d M Y', strtotime($startDate)) . ' – ' . date('d M Y', strtotime($endDate));
        }

        $modeLabel = $isOverride ? 'Viewing historical semester' : 'Viewing current semester';
        $selectId = 'term-select-' . $renderCount;

        if (!$assetsRendered) {
            $assetsRendered = true;
            echo '<style>
.term-switch-wrapper { margin-bottom: 24px; }
.term-context-bar { display: flex; justify-content: space-between; align-items: center; gap: 16px; background: #fff; border-radius: 12px; padding: 16px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.term-context-info h3 { margin: 0; font-size: 1.1rem; color: #2c3e50; }
.term-context-info span { display: block; font-size: 0.9rem; color: #555; }
.term-context-actions { display: flex; gap: 12px; flex-wrap: wrap; }
.term-context-actions button { background-color: #A6192E; border: none; color: #fff; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-size: 0.9rem; }
.term-context-actions button.secondary { background-color: #6c757d; }
.term-context-actions button:disabled { opacity: 0.6; cursor: not-allowed; }
.term-switch-panel { display: none; flex-wrap: wrap; align-items: flex-end; gap: 12px; background: #fff; border-radius: 12px; padding: 16px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-top: 16px; }
.term-switch-panel.open { display: flex; }
.term-switch-panel .field-group { display: flex; flex-direction: column; gap: 6px; }
.term-switch-panel label { font-weight: 600; font-size: 0.9rem; color: #2c3e50; }
.term-switch-panel select { min-width: 240px; padding: 10px; border-radius: 8px; border: 1px solid #d1d5db; }
.term-switch-panel .inline-controls { display: flex; gap: 12px; }
@media (max-width: 768px) {
	.term-context-bar { flex-direction: column; align-items: flex-start; }
	.term-context-actions { width: 100%; }
	.term-context-actions button { flex: 1; }
	.term-switch-panel { flex-direction: column; align-items: stretch; }
	.term-switch-panel .inline-controls { flex-direction: column; width: 100%; }
}
</style>';
            echo '<script>
(function(){
    function bindSwitcher(scope){
        const panel = scope.querySelector("[data-term-panel]");
        const trigger = scope.querySelector("[data-term-trigger]");
        const applyBtn = panel ? panel.querySelector("[data-term-apply]") : null;
        const cancelBtn = panel ? panel.querySelector("[data-term-cancel]") : null;
        const selectEl = panel ? panel.querySelector("[data-term-select]") : null;
        const resetBtn = scope.querySelector("[data-term-reset]");
        if (trigger && panel) {
            trigger.addEventListener("click", function(){ panel.classList.toggle("open"); });
        }
        if (cancelBtn && panel) {
            cancelBtn.addEventListener("click", function(){ panel.classList.remove("open"); });
        }
        if (applyBtn && selectEl) {
            applyBtn.addEventListener("click", function(){
                const choice = selectEl.value;
                if (!choice) {
                    alert("Please choose a semester to view.");
                    return;
                }
                const params = new URLSearchParams();
                params.append("term_id", choice);
                if (panel.dataset.school) {
                    params.append("school", panel.dataset.school);
                }
                fetch("set_academic_context.php", {
                    method: "POST",
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                    body: params
                }).then(function(response){ return response.json(); }).then(function(data){
                    if (data.status === "ok") {
                        window.location.reload();
                        return;
                    }
                    alert(data.message || "Unable to switch semester.");
                }).catch(function(){ alert("Unable to switch semester."); });
            });
        }
        if (resetBtn) {
            resetBtn.addEventListener("click", function(){
                const params = new URLSearchParams();
                params.append("action", "reset");
                fetch("set_academic_context.php", {
                    method: "POST",
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                    body: params
                }).then(function(response){ return response.json(); }).then(function(data){
                    if (data.status === "ok") {
                        window.location.reload();
                        return;
                    }
                    alert(data.message || "Unable to restore current semester.");
                }).catch(function(){ alert("Unable to restore current semester."); });
            });
        }
    }
    document.addEventListener("DOMContentLoaded", function(){
        document.querySelectorAll("[data-term-scope]").forEach(bindSwitcher);
    });
})();
</script>';
        }

        echo '<div class="term-switch-wrapper" data-term-scope>';
        echo '<div class="term-context-bar">';
        echo '<div class="term-context-info">';
        echo '<h3>' . htmlspecialchars($termTitle, ENT_QUOTES, 'UTF-8') . '</h3>';
        echo '<span>' . htmlspecialchars($termDatesLabel, ENT_QUOTES, 'UTF-8') . '</span>';
        echo '<span>' . htmlspecialchars($modeLabel, ENT_QUOTES, 'UTF-8') . '</span>';
        echo '</div>';
        echo '<div class="term-context-actions">';
        $switchDisabled = empty($terms) ? ' disabled' : '';
        echo '<button type="button" data-term-trigger' . $switchDisabled . '>Switch Semester</button>';
        if ($isOverride) {
            echo '<button type="button" class="secondary" data-term-reset>Return to current</button>';
        }
        echo '</div>';
        echo '</div>';

        if (!empty($terms)) {
            echo '<div class="term-switch-panel" data-term-panel data-school="' . htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') . '">';
            echo '<div class="field-group">';
            echo '<label for="' . htmlspecialchars($selectId, ENT_QUOTES, 'UTF-8') . '">Choose a semester</label>';
            echo '<select id="' . htmlspecialchars($selectId, ENT_QUOTES, 'UTF-8') . '" data-term-select>';
            $activeId = $active['id'] ?? null;
            foreach ($terms as $termRow) {
                $selected = ($activeId !== null && (int)$termRow['id'] === (int)$activeId) ? ' selected' : '';
                echo '<option value="' . (int)$termRow['id'] . '"' . $selected . '>' . htmlspecialchars($termRow['label'], ENT_QUOTES, 'UTF-8') . '</option>';
            }
            echo '</select>';
            echo '</div>';
            echo '<div class="inline-controls">';
            echo '<button type="button" data-term-apply>Apply</button>';
            echo '<button type="button" class="secondary" data-term-cancel>Cancel</button>';
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
    }
}
