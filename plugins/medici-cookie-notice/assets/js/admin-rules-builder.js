/**
 * Medici Cookie Notice - Rules Builder
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

/* global mcnRulesData, jQuery */

(function ($) {
	'use strict';

	const MCNRulesBuilder = {
		groups: [],
		evaluators: {},
		tempIdCounter: 0,

		init() {
			const data = mcnRulesData || {};
			this.evaluators = data.evaluators || {};

			this.loadGroups();
			this.bindEvents();
			this.initSortable();
		},

		loadGroups() {
			$.ajax({
				url: mcnRulesData.ajax_url,
				type: 'POST',
				data: {
					action: 'mcn_get_rule_groups',
					nonce: mcnRulesData.nonce,
				},
				success: (response) => {
					if (response.success) {
						this.groups = response.data.groups || [];
						this.evaluators = response.data.evaluators || this.evaluators;
						this.renderGroups();
					}
				},
			});
		},

		renderGroups() {
			const $container = $('#mcn-rule-groups');
			$container.empty();

			if (this.groups.length === 0) {
				$container.html(`
          <div class="mcn-no-groups">
            <p>Немає умовних правил.</p>
            <button type="button" class="button button-primary mcn-add-group">
              + Додати групу правил
            </button>
          </div>
        `);
				return;
			}

			this.groups.forEach((group) => {
				$container.append(this.renderGroup(group));
			});
		},

		renderGroup(group) {
			const rules = group.rules || [];
			const rulesHtml = rules.map((rule) => this.renderRule(rule)).join('');

			return `
        <div class="mcn-rule-group" data-group-id="${group.id}">
          <div class="mcn-rule-group-header">
            <span class="mcn-group-handle dashicons dashicons-menu"></span>
            <div class="mcn-group-name">
              <input type="text" value="${this.escapeHtml(group.name)}"
                     placeholder="Назва групи" class="mcn-group-name-input" />
            </div>
            <div class="mcn-group-operator">
              <label>Правила:</label>
              <select class="mcn-group-operator-select">
                <option value="AND" ${group.operator === 'AND' ? 'selected' : ''}>Всі (AND)</option>
                <option value="OR" ${group.operator === 'OR' ? 'selected' : ''}>Будь-яке (OR)</option>
              </select>
            </div>
            <div class="mcn-group-action">
              <label>Дія:</label>
              <select class="mcn-group-action-select">
                <option value="show" ${group.action === 'show' ? 'selected' : ''}>Показати банер</option>
                <option value="hide" ${group.action === 'hide' ? 'selected' : ''}>Приховати банер</option>
              </select>
            </div>
            <label class="mcn-group-toggle">
              <input type="checkbox" class="mcn-group-active-checkbox"
                     ${group.is_active === '1' || group.is_active === 1 ? 'checked' : ''} />
              Активна
            </label>
            <button type="button" class="button-link mcn-group-delete" title="Видалити групу">
              <span class="dashicons dashicons-trash"></span>
            </button>
          </div>
          <div class="mcn-rule-group-body">
            <div class="mcn-rules-list">
              ${rulesHtml || '<p class="mcn-no-rules">Додайте правила до цієї групи</p>'}
            </div>
            <button type="button" class="button mcn-add-rule">
              <span class="dashicons dashicons-plus-alt2"></span> Додати правило
            </button>
          </div>
        </div>
      `;
		},

		renderRule(rule) {
			const evaluator = this.evaluators[rule.rule_type];
			const operators = evaluator?.operators || {};
			const options = evaluator?.options || null;
			const fieldType = evaluator?.fieldType || 'text';

			const typeOptions = Object.keys(this.evaluators)
				.map(
					(key) =>
						`<option value="${key}" ${key === rule.rule_type ? 'selected' : ''}>
          ${this.evaluators[key].label}
        </option>`
				)
				.join('');

			const operatorOptions = Object.keys(operators)
				.map(
					(key) =>
						`<option value="${key}" ${key === rule.operator ? 'selected' : ''}>
          ${operators[key]}
        </option>`
				)
				.join('');

			let valueField = '';
			if (options) {
				const valueOptions = Object.keys(options)
					.map(
						(key) =>
							`<option value="${key}" ${key === rule.value ? 'selected' : ''}>
            ${options[key]}
          </option>`
					)
					.join('');
				valueField = `<select class="mcn-rule-value-select">${valueOptions}</select>`;
			} else {
				valueField = `<input type="${fieldType}" class="mcn-rule-value-input"
                            value="${this.escapeHtml(rule.value || '')}"
                            placeholder="Значення" />`;
			}

			return `
        <div class="mcn-rule-row" data-rule-id="${rule.id}">
          <select class="mcn-rule-type-select">${typeOptions}</select>
          <select class="mcn-rule-operator-select">${operatorOptions}</select>
          ${valueField}
          <span class="mcn-rule-delete dashicons dashicons-dismiss" title="Видалити правило"></span>
        </div>
      `;
		},

		bindEvents() {
			const self = this;

			// Add group
			$(document).on('click', '.mcn-add-group, #mcn-add-group-btn', () => {
				this.addGroup();
			});

			// Delete group
			$(document).on('click', '.mcn-group-delete', function () {
				const $group = $(this).closest('.mcn-rule-group');
				const groupId = $group.data('group-id');

				if (confirm('Видалити цю групу правил?')) {
					self.deleteGroup(groupId, $group);
				}
			});

			// Add rule
			$(document).on('click', '.mcn-add-rule', function () {
				const $group = $(this).closest('.mcn-rule-group');
				self.addRule($group);
			});

			// Delete rule
			$(document).on('click', '.mcn-rule-delete', function () {
				$(this).closest('.mcn-rule-row').remove();
			});

			// Rule type change - update operators and value field
			$(document).on('change', '.mcn-rule-type-select', function () {
				const $row = $(this).closest('.mcn-rule-row');
				const type = $(this).val();
				self.updateRuleFields($row, type);
			});

			// Save all changes
			$(document).on('click', '#mcn-save-rules', () => {
				this.saveAllGroups();
			});

			// Group field changes - mark as dirty
			$(document).on(
				'change',
				'.mcn-group-name-input, .mcn-group-operator-select, .mcn-group-action-select, .mcn-group-active-checkbox',
				function () {
					$(this).closest('.mcn-rule-group').addClass('mcn-dirty');
				}
			);
		},

		initSortable() {
			if ($.fn.sortable) {
				$('#mcn-rule-groups').sortable({
					handle: '.mcn-group-handle',
					update: () => {
						this.reorderGroups();
					},
				});
			}
		},

		addGroup() {
			const tempId = 'new_' + ++this.tempIdCounter;
			const newGroup = {
				id: tempId,
				name: '',
				operator: 'AND',
				action: 'show',
				is_active: 1,
				rules: [],
			};

			this.groups.push(newGroup);
			$('#mcn-rule-groups .mcn-no-groups').remove();
			$('#mcn-rule-groups').append(this.renderGroup(newGroup));
		},

		addRule($group) {
			const defaultType = Object.keys(this.evaluators)[0] || 'page_type';
			const evaluator = this.evaluators[defaultType];
			const defaultOperator = Object.keys(evaluator?.operators || {})[0] || 'is';

			const rule = {
				id: 'new_' + ++this.tempIdCounter,
				rule_type: defaultType,
				operator: defaultOperator,
				value: '',
			};

			const $list = $group.find('.mcn-rules-list');
			$list.find('.mcn-no-rules').remove();
			$list.append(this.renderRule(rule));
		},

		updateRuleFields($row, type) {
			const evaluator = this.evaluators[type];
			if (!evaluator) return;

			// Update operators
			const $operatorSelect = $row.find('.mcn-rule-operator-select');
			$operatorSelect.empty();
			Object.keys(evaluator.operators).forEach((key) => {
				$operatorSelect.append(`<option value="${key}">${evaluator.operators[key]}</option>`);
			});

			// Update value field
			const $valueContainer = $row.find('.mcn-rule-value-select, .mcn-rule-value-input');
			const options = evaluator.options;

			if (options) {
				const valueOptions = Object.keys(options)
					.map((key) => `<option value="${key}">${options[key]}</option>`)
					.join('');
				$valueContainer.replaceWith(
					`<select class="mcn-rule-value-select">${valueOptions}</select>`
				);
			} else {
				$valueContainer.replaceWith(
					`<input type="${evaluator.fieldType || 'text'}" class="mcn-rule-value-input" placeholder="Значення" />`
				);
			}
		},

		deleteGroup(groupId, $group) {
			if (String(groupId).startsWith('new_')) {
				// Not saved yet, just remove
				$group.fadeOut(() => $group.remove());
				return;
			}

			$.ajax({
				url: mcnRulesData.ajax_url,
				type: 'POST',
				data: {
					action: 'mcn_delete_rule_group',
					nonce: mcnRulesData.nonce,
					id: groupId,
				},
				success: (response) => {
					if (response.success) {
						$group.fadeOut(() => $group.remove());
					} else {
						alert(response.data.message || 'Error deleting group');
					}
				},
			});
		},

		getGroupData($group) {
			const rules = [];
			$group.find('.mcn-rule-row').each(function () {
				rules.push({
					rule_type: $(this).find('.mcn-rule-type-select').val(),
					operator: $(this).find('.mcn-rule-operator-select').val(),
					value:
						$(this).find('.mcn-rule-value-select').val() ||
						$(this).find('.mcn-rule-value-input').val(),
				});
			});

			return {
				id: $group.data('group-id'),
				name: $group.find('.mcn-group-name-input').val(),
				operator: $group.find('.mcn-group-operator-select').val(),
				action: $group.find('.mcn-group-action-select').val(),
				is_active: $group.find('.mcn-group-active-checkbox').is(':checked') ? 1 : 0,
				rules: rules,
			};
		},

		saveAllGroups() {
			const $btn = $('#mcn-save-rules');
			$btn.prop('disabled', true).text('Збереження...');

			const promises = [];

			$('.mcn-rule-group').each((i, el) => {
				const $group = $(el);
				const data = this.getGroupData($group);

				promises.push(
					$.ajax({
						url: mcnRulesData.ajax_url,
						type: 'POST',
						data: {
							action: 'mcn_save_rule_group',
							nonce: mcnRulesData.nonce,
							group: JSON.stringify(data),
						},
					})
				);
			});

			Promise.all(promises)
				.then(() => {
					alert('Правила збережено!');
					this.loadGroups(); // Reload to get real IDs
				})
				.catch(() => {
					alert('Помилка збереження');
				})
				.finally(() => {
					$btn.prop('disabled', false).text('Зберегти правила');
				});
		},

		reorderGroups() {
			const order = [];
			$('.mcn-rule-group').each(function () {
				const id = $(this).data('group-id');
				if (!String(id).startsWith('new_')) {
					order.push(id);
				}
			});

			if (order.length === 0) return;

			$.ajax({
				url: mcnRulesData.ajax_url,
				type: 'POST',
				data: {
					action: 'mcn_reorder_groups',
					nonce: mcnRulesData.nonce,
					order: order,
				},
			});
		},

		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text || '';
			return div.innerHTML;
		},
	};

	$(document).ready(() => {
		MCNRulesBuilder.init();
	});
})(jQuery);
