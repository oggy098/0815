(function () {
	'use strict';

	var cfg = window.nextgenDashboard || {};
	var root = document.getElementById('ngd');

	if (!root || !cfg.statusUrl) {
		return;
	}

	var state = {
		loading: false,
		noteTimer: 0,
		noteDirty: false,
		pluginBusy: false
	};

	function text(key, fallback) {
		return cfg.i18n && cfg.i18n[key] ? cfg.i18n[key] : fallback;
	}

	function reduced() {
		return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	}

	function headers(json) {
		var result = { Accept: 'application/json' };

		if (cfg.nonce) {
			result['X-WP-Nonce'] = cfg.nonce;
		}

		if (json) {
			result['Content-Type'] = 'application/json';
		}

		return result;
	}

	function tile(area) {
		return root.querySelector('.ngd-tile[data-area="' + area + '"]');
	}

	function field(area, name) {
		var box = tile(area);

		return box ? box.querySelector('[data-field="' + name + '"]') : null;
	}

	function paintMeta(data) {
		var meta = document.getElementById('ngd-foot-meta');
		var versions = data && data.versions ? data.versions : {};
		var wp = versions.wp || root.dataset.wp || '—';
		var php = versions.php || root.dataset.php || '—';
		var updated = (data && data.updated) || root.dataset.updated || '—';

		if (meta) {
			meta.textContent = 'WP ' + wp + ' · PHP ' + php + ' · ' + text('updated', 'Zuletzt aktualisiert:') + ' ' + updated;
		}
	}

	function countTo(el, target, fromZero) {
		target = parseInt(target, 10) || 0;

		var from = fromZero ? 0 : (parseInt(el.getAttribute('data-count'), 10) || 0);
		var token = (el._ngdToken || 0) + 1;

		el._ngdToken = token;
		el.setAttribute('data-count', String(target));

		if (reduced() || from === target) {
			el.textContent = String(target);
			return;
		}

		var start = window.performance.now();

		function frame(now) {
			if (el._ngdToken !== token) {
				return;
			}

			var progress = Math.min(1, (now - start) / 650);
			var eased = 1 - Math.pow(1 - progress, 3);

			el.textContent = String(Math.round(from + (target - from) * eased));

			if (progress < 1) {
				window.requestAnimationFrame(frame);
			}
		}

		window.requestAnimationFrame(frame);
	}

	function bootCounts() {
		root.querySelectorAll('[data-count]').forEach(function (el, index) {
			var target = el.getAttribute('data-count');

			window.setTimeout(function () {
				countTo(el, target, true);
			}, reduced() ? 0 : index * 40);
		});
	}

	function paintSystem(system) {
		Object.keys(system || {}).forEach(function (key) {
			var row = system[key] || {};
			var value = field('status', key);
			var dot = tile('status') ? tile('status').querySelector('[data-dot="' + key + '"]') : null;
			var level = row.dot === 'warn' || row.dot === 'bad' ? row.dot : 'ok';

			if (value) {
				value.textContent = row.value || '—';
			}

			if (dot) {
				dot.className = 'ngd-dot ngd-dot--' + level;
			}
		});
	}

	function validPluginFile(file) {
		return /^nextgen-[A-Za-z0-9][A-Za-z0-9._-]*\/[A-Za-z0-9][A-Za-z0-9._-]*\.php$/.test(file || '');
	}

	function paintPlugins(list) {
		var ul = document.getElementById('ngd-plugins');

		if (!ul || state.pluginBusy) {
			return;
		}

		ul.replaceChildren();

		if (!list || !list.length) {
			var empty = document.createElement('li');
			empty.className = 'ngd-muted';
			empty.textContent = text('noPlugins', 'Keine NextGen-Plugins gefunden.');
			ul.appendChild(empty);
			return;
		}

		list.forEach(function (item) {
			var li = document.createElement('li');
			var name = document.createElement('span');
			var ver = document.createElement('span');

			li.className = 'ngd-plugin';
			name.className = 'ngd-plugin__name';
			name.textContent = item.name || '';
			ver.className = 'ngd-plugin__ver';
			ver.textContent = item.version || '';
			li.appendChild(name);
			li.appendChild(ver);

			if (cfg.canPlugins === '1' && validPluginFile(item.file)) {
				var button = document.createElement('button');
				var knob = document.createElement('span');
				var label = document.createElement('span');

				button.type = 'button';
				button.className = 'ngd-switch';
				button.setAttribute('role', 'switch');
				button.setAttribute('aria-checked', item.active ? 'true' : 'false');
				button.setAttribute('data-plugin', item.file);
				button.setAttribute('data-self', item.self ? '1' : '0');
				knob.className = 'ngd-switch__knob';
				label.className = 'ngd-sr';
				label.textContent = item.name || '';
				button.appendChild(knob);
				button.appendChild(label);
				li.appendChild(button);
			} else {
				var stateLabel = document.createElement('span');
				stateLabel.className = 'ngd-plugin__state';
				stateLabel.textContent = item.active ? text('active', 'aktiv') : text('inactive', 'inaktiv');
				li.appendChild(stateLabel);
			}

			ul.appendChild(li);
		});
	}

	function paintFigures(area, values) {
		Object.keys(values || {}).forEach(function (key) {
			if (key === 'spark') {
				return;
			}

			var el = field(area, key);

			if (!el) {
				return;
			}

			if (el.hasAttribute('data-count')) {
				countTo(el, values[key], false);
			} else {
				el.textContent = String(values[key]);
			}
		});
	}

	function sparkSvg(values) {
		var series = (values || []).map(function (value) {
			return parseInt(value, 10) || 0;
		});

		if (series.length < 2) {
			series = [0, 0];
		}

		var max = Math.max.apply(null, series.concat([1]));
		var width = 160;
		var height = 36;
		var last = series.length - 1;
		var points = series.map(function (value, index) {
			var x = (index / last) * width;
			var y = (height - 4) - ((value / max) * (height - 8));
			return (Math.round(x * 10) / 10) + ',' + (Math.round(y * 10) / 10);
		}).join(' ');
		var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		var line = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');

		svg.setAttribute('class', 'ngd-spark');
		svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
		svg.setAttribute('role', 'img');
		svg.setAttribute('aria-label', text('days', 'Beiträge der letzten 30 Tage'));
		line.setAttribute('points', points);
		svg.appendChild(line);

		return svg;
	}

	function paintContent(content) {
		paintFigures('inhalt', content);

		var wrap = document.getElementById('ngd-spark');

		if (wrap && content && content.spark) {
			wrap.replaceChildren(sparkSvg(content.spark));
		}
	}

	function paintDeploy(deploy) {
		var theme = field('deploy', 'theme');
		var plugins = field('deploy', 'plugins');

		if (theme) {
			theme.textContent = deploy.theme || '—';
		}

		if (plugins) {
			plugins.textContent = deploy.plugins || '—';
		}
	}

	function safeGithubUrl(url) {
		try {
			var parsed = new URL(url);

			if (parsed.protocol !== 'https:') {
				return '';
			}

			if (parsed.hostname !== 'github.com' && parsed.hostname !== 'www.github.com') {
				return '';
			}

			return parsed.href;
		} catch (error) {
			return '';
		}
	}

	function paintGithub(admin) {
		var box = document.getElementById('ngd-github');

		if (!box || cfg.isAdmin !== '1') {
			return;
		}

		box.replaceChildren();

		if (!admin || !admin.repo) {
			return;
		}

		if (!admin.github) {
			var miss = document.createElement('p');
			miss.className = 'ngd-muted';
			miss.textContent = text('commitMiss', 'Commit nicht geladen.');
			box.appendChild(miss);
			return;
		}

		var row = document.createElement('p');
		var url = safeGithubUrl(admin.github.url || '');
		var code = document.createElement('code');

		row.className = 'ngd-commit';
		code.textContent = admin.github.sha || '';

		if (url) {
			var link = document.createElement('a');
			link.href = url;
			link.target = '_blank';
			link.rel = 'noopener noreferrer';
			link.appendChild(code);
			row.appendChild(link);
		} else {
			row.appendChild(code);
		}

		row.appendChild(document.createTextNode(' '));
		var message = document.createElement('span');
		message.textContent = admin.github.message || '';
		row.appendChild(message);

		if (admin.github.date) {
			row.appendChild(document.createTextNode(' '));
			var time = document.createElement('time');
			time.textContent = admin.github.date;
			row.appendChild(time);
		}

		box.appendChild(row);
	}

	function selectionInside(node) {
		var selection = window.getSelection();

		if (!selection || !selection.anchorNode || !node) {
			return false;
		}

		return node.contains(selection.anchorNode);
	}

	function paintLog(entries) {
		var log = document.getElementById('ngd-log');

		if (!log || cfg.isAdmin !== '1' || selectionInside(log)) {
			return;
		}

		log.replaceChildren();

		if (!entries || !entries.length) {
			var empty = document.createElement('p');
			empty.className = 'ngd-muted';
			empty.textContent = text('emptyLog', 'Keine Einträge.');
			log.appendChild(empty);
			return;
		}

		entries.forEach(function (entry) {
			var line = document.createElement('div');
			var level = entry.level === 'error' || entry.level === 'warn' ? entry.level : 'info';

			line.className = 'ngd-log__line ngd-log__line--' + level;
			line.textContent = entry.text || '';
			log.appendChild(line);
		});
	}

	function paintNotes(notes) {
		var area = document.getElementById('ngd-notes');

		if (!area || cfg.isAdmin !== '1' || state.noteDirty || document.activeElement === area) {
			return;
		}

		if (area.value !== notes) {
			area.value = notes;
		}
	}

	function applyStatus(data) {
		if (!data) {
			return;
		}

		paintMeta(data);

		if (data.system) {
			paintSystem(data.system);
		}

		if (data.plugins && (cfg.canPlugins !== '1' || !data.plugins.length || data.plugins[0].file)) {
			paintPlugins(data.plugins);
		}

		if (data.updates) {
			paintFigures('updates', data.updates);
		}

		if (data.content) {
			paintContent(data.content);
		}

		if (data.deploy) {
			paintDeploy(data.deploy);
		}

		if (cfg.isAdmin === '1' && data.admin) {
			paintLog(data.admin.log || []);
			paintNotes(typeof data.admin.notes === 'string' ? data.admin.notes : '');
			paintGithub(data.admin);
		}
	}

	function refresh() {
		if (state.loading) {
			return;
		}

		state.loading = true;

		window.fetch(cfg.statusUrl, {
			headers: headers(false),
			credentials: 'same-origin',
			cache: 'no-store'
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('status');
			}

			return response.json();
		}).then(applyStatus).catch(function () {
			/* Bestehende Kacheln bleiben stehen. */
		}).finally(function () {
			state.loading = false;
		});
	}

	function tick() {
		var clock = document.getElementById('ngd-clock');

		if (!clock) {
			return;
		}

		var now = new Date();
		var time = new Intl.DateTimeFormat('de-CH', {
			hour: '2-digit',
			minute: '2-digit',
			second: '2-digit',
			hourCycle: 'h23',
			timeZone: 'Europe/Zurich'
		}).format(now);
		var date = new Intl.DateTimeFormat('de-CH', {
			day: '2-digit',
			month: '2-digit',
			year: 'numeric',
			timeZone: 'Europe/Zurich'
		}).format(now);

		clock.textContent = time + ' · ' + date;
		clock.setAttribute('datetime', now.toISOString());
	}

	function bindTheme() {
		var button = document.getElementById('ngd-theme');

		if (!button) {
			return;
		}

		function label() {
			var light = document.documentElement.getAttribute('data-ngd-theme') === 'light';
			button.setAttribute('aria-label', light ? text('themeDark', 'Dunkle Darstellung') : text('themeLight', 'Helle Darstellung'));
		}

		label();

		button.addEventListener('click', function () {
			var next = document.documentElement.getAttribute('data-ngd-theme') === 'light' ? 'dark' : 'light';

			document.documentElement.setAttribute('data-ngd-theme', next);
			document.documentElement.style.colorScheme = next === 'light' ? 'light' : 'dark';

			try {
				window.localStorage.setItem('ngd-theme', next);
			} catch (error) {
				/* Private Mode kann localStorage sperren. */
			}

			label();
		});
	}

	function bindPlugins() {
		var box = document.getElementById('ngd-body-plugins');

		if (!box) {
			return;
		}

		box.addEventListener('click', function (event) {
			var button = event.target.closest ? event.target.closest('.ngd-switch') : null;

			if (!button || cfg.canPlugins !== '1' || state.pluginBusy) {
				return;
			}

			var file = button.getAttribute('data-plugin');
			var turningOn = button.getAttribute('aria-checked') !== 'true';

			if (!validPluginFile(file)) {
				return;
			}

			if (!turningOn && button.getAttribute('data-self') === '1') {
				if (!window.confirm(text('confirmSelf', 'Dieses Plugin steuert das Dashboard. Trotzdem deaktivieren?'))) {
					return;
				}
			}

			var previous = turningOn ? 'false' : 'true';
			button.setAttribute('aria-checked', turningOn ? 'true' : 'false');
			button.disabled = true;
			state.pluginBusy = true;

			window.fetch(cfg.pluginUrl, {
				method: 'POST',
				headers: headers(true),
				credentials: 'same-origin',
				body: JSON.stringify({ plugin: file, active: turningOn })
			}).then(function (response) {
				if (!response.ok) {
					throw new Error('plugin');
				}

				return response.json();
			}).then(function (payload) {
				state.pluginBusy = false;

				if (payload && payload.plugins) {
					paintPlugins(payload.plugins);
				}
			}).catch(function () {
				button.setAttribute('aria-checked', previous);
				button.disabled = false;
				button.title = text('toggleError', 'Konnte nicht geändert werden.');
			}).finally(function () {
				state.pluginBusy = false;
			});
		});
	}

	function bindLog() {
		var button = document.getElementById('ngd-clear-log');

		if (!button) {
			return;
		}

		button.addEventListener('click', function () {
			if (!window.confirm(text('confirmClear', 'Debug-Log wirklich leeren?'))) {
				return;
			}

			button.disabled = true;

			window.fetch(cfg.logUrl, {
				method: 'POST',
				headers: headers(true),
				credentials: 'same-origin',
				body: '{}'
			}).then(function (response) {
				if (!response.ok) {
					throw new Error('log');
				}

				return response.json();
			}).then(function (payload) {
				paintLog(payload && payload.log ? payload.log : []);
			}).catch(function () {
				button.title = text('logError', 'Log konnte nicht geleert werden.');
			}).finally(function () {
				button.disabled = false;
			});
		});
	}

	function showSkeletons(out) {
		out.replaceChildren();

		for (var i = 0; i < 3; i++) {
			var bar = document.createElement('div');
			bar.className = 'ngd-skel';
			out.appendChild(bar);
		}
	}

	function bindSandbox() {
		var form = document.getElementById('ngd-sandbox-form');
		var input = document.getElementById('ngd-shortcode');
		var out = document.getElementById('ngd-sandbox-out');

		if (!form || !input || !out) {
			return;
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			var shortcode = input.value.trim();

			if (!shortcode) {
				out.replaceChildren();
				var hint = document.createElement('p');
				hint.className = 'ngd-muted';
				hint.textContent = text('sandboxHint', 'Shortcode eingeben.');
				out.appendChild(hint);
				return;
			}

			showSkeletons(out);

			window.fetch(cfg.sandboxUrl, {
				method: 'POST',
				headers: headers(true),
				credentials: 'same-origin',
				body: JSON.stringify({ shortcode: shortcode })
			}).then(function (response) {
				if (!response.ok) {
					throw new Error('sandbox');
				}

				return response.json();
			}).then(function (payload) {
				var html = payload && typeof payload.html === 'string' ? payload.html : '';
				out.innerHTML = html || ('<p class="ngd-muted"></p>');

				if (!html) {
					out.textContent = text('sandboxEmpty', 'Keine Ausgabe.');
				}
			}).catch(function () {
				out.replaceChildren();
				var error = document.createElement('p');
				error.className = 'ngd-muted';
				error.textContent = text('sandboxError', 'Ausgabe fehlgeschlagen.');
				out.appendChild(error);
			});
		});
	}

	function setSave(mode, message) {
		var el = document.getElementById('ngd-save');

		if (!el) {
			return;
		}

		el.className = 'ngd-save' + (mode ? ' is-' + mode : '');
		el.textContent = message || '';
	}

	function bindNotes() {
		var area = document.getElementById('ngd-notes');

		if (!area) {
			return;
		}

		area.addEventListener('input', function () {
			state.noteDirty = true;
			window.clearTimeout(state.noteTimer);
			state.noteTimer = window.setTimeout(function () {
				setSave('', text('saving', 'Speichert …'));

				window.fetch(cfg.notesUrl, {
					method: 'POST',
					headers: headers(true),
					credentials: 'same-origin',
					body: JSON.stringify({ notes: area.value })
				}).then(function (response) {
					if (!response.ok) {
						throw new Error('notes');
					}

					state.noteDirty = false;
					setSave('ok', text('saved', 'Gespeichert'));
				}).catch(function () {
					setSave('error', text('saveError', 'Konnte nicht gespeichert werden.'));
				});
			}, 700);
		});
	}

	function bootClock() {
		var year = document.getElementById('ngd-year');

		tick();
		window.setInterval(tick, 1000);

		if (year) {
			year.textContent = new Intl.DateTimeFormat('de-CH', {
				year: 'numeric',
				timeZone: 'Europe/Zurich'
			}).format(new Date());
		}
	}

	paintMeta({
		versions: { wp: root.dataset.wp, php: root.dataset.php },
		updated: root.dataset.updated
	});
	bootClock();
	bindTheme();
	bootCounts();
	bindPlugins();
	bindLog();
	bindSandbox();
	bindNotes();
	window.setInterval(refresh, Number(cfg.poll) || 60000);
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden) {
			refresh();
		}
	});
})();
