(function () {
  'use strict';

  function normalize(value) {
    return String(value).normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
  }

  function searchIndex(entries, query, limit) {
    var words = normalize(query).slice(0, 120).split(' ').filter(Boolean);
    if (!words.length) { return []; }
    return entries.map(function (entry, order) {
      var title = normalize(entry.title);
      var content = normalize(entry.content);
      if (!words.every(function (word) { return (title + ' ' + content).includes(word); })) { return null; }
      return { entry: entry, order: order, score: words.reduce(function (score, word) { return score + (title.includes(word) ? 5 : 1); }, 0) };
    }).filter(Boolean).sort(function (a, b) { return b.score - a.score || a.order - b.order; }).slice(0, limit || 8).map(function (match) { return match.entry; });
  }

  // Allow the pure search functions to be checked without a browser or dependencies.
  if (typeof module !== 'undefined' && module.exports) { module.exports = { normalize: normalize, searchIndex: searchIndex }; }
  if (typeof document === 'undefined') { return; }

  document.documentElement.classList.add('sm-js');
  var menu = document.getElementById('sm-doc-menu');
  var mobile = window.matchMedia('(max-width: 900px)');
  var search = document.getElementById('sm-search-input');
  var resultPanel = document.getElementById('sm-search-results');
  var resultList = document.getElementById('sm-search-matches');
  var searchStatus = document.getElementById('sm-search-status');
  var copyStatus = document.getElementById('sm-copy-status');
  var navLinks = Array.from(document.querySelectorAll('#sm-doc-nav a'));
  var sections = Array.from(document.querySelectorAll('main > section[id]'));
  var entries = Array.from(document.querySelectorAll('[data-searchable]')).map(function (element) {
    var searchable = element.cloneNode(true);
    searchable.querySelectorAll('[data-search-exclude]').forEach(function (excluded) { excluded.remove(); });
    return { id: element.id, title: element.dataset.title, edition: element.dataset.edition, content: searchable.textContent.replace(/\s+/g, ' ').trim() };
  });

  document.querySelectorAll('.sm-source-editor').forEach(function (editor) {
    var tabList = editor.querySelector('[data-source-tabs]');
    var tabs = Array.from(editor.querySelectorAll('[data-source-tab]'));
    var panels = Array.from(editor.querySelectorAll('[data-source-panel]'));
    tabList.setAttribute('role', 'tablist');
    function selectTab(tab, moveFocus) {
      tabs.forEach(function (item) {
        var selected = item === tab;
        item.setAttribute('aria-selected', String(selected));
        item.tabIndex = selected ? 0 : -1;
      });
      panels.forEach(function (panel) { panel.hidden = panel.id !== tab.dataset.sourceTab; });
      if (moveFocus) { tab.focus(); }
    }
    tabs.forEach(function (tab, index) {
      tab.hidden = false;
      tab.setAttribute('role', 'tab');
      tab.setAttribute('aria-controls', tab.dataset.sourceTab);
      tab.addEventListener('click', function () { selectTab(tab, false); });
      tab.addEventListener('keydown', function (event) {
        var next;
        if (event.key === 'ArrowRight') { next = (index + 1) % tabs.length; }
        if (event.key === 'ArrowLeft') { next = (index + tabs.length - 1) % tabs.length; }
        if (event.key === 'Home') { next = 0; }
        if (event.key === 'End') { next = tabs.length - 1; }
        if (next !== undefined) { event.preventDefault(); selectTab(tabs[next], true); }
      });
    });
    panels.forEach(function (panel, index) {
      panel.setAttribute('role', 'tabpanel');
      panel.setAttribute('aria-labelledby', tabs[index].id);
      var code = panel.querySelector('code');
      var gutter = panel.querySelector('.sm-source-gutter');
      var count = code.textContent.replace(/\n$/, '').split('\n').length;
      gutter.textContent = Array.from({ length: count }, function (_, line) { return line + 1; }).join('\n');
      gutter.hidden = false;
    });
    if (tabs.length) { selectTab(tabs[0], false); }
  });

  function fitMenu() { menu.open = !mobile.matches; }
  fitMenu();
  if (mobile.addEventListener) { mobile.addEventListener('change', fitMenu); }
  else { mobile.addListener(fitMenu); }

  function revealHash(hash) {
    var id;
    try { id = decodeURIComponent(hash.replace(/^#/, '')); } catch (error) { return; }
    var target = document.getElementById(id);
    if (!target) { return; }
    if (target.tagName === 'DETAILS') { target.open = true; }
    for (var parent = target.parentElement; parent; parent = parent.parentElement) {
      if (parent.tagName === 'DETAILS') { parent.open = true; }
    }
  }
  revealHash(window.location.hash);
  window.addEventListener('hashchange', function () { revealHash(window.location.hash); });
  navLinks.forEach(function (link) {
    link.addEventListener('click', function () { if (mobile.matches) { menu.open = false; } closeSearch(); });
  });

  function closeSearch() { resultPanel.hidden = true; search.setAttribute('aria-expanded', 'false'); }
  function renderSearch() {
    var query = search.value.trim();
    resultList.replaceChildren();
    if (!query) { closeSearch(); return; }
    var matches = searchIndex(entries, query);
    searchStatus.textContent = matches.length ? matches.length + ' matching sections · select a result to jump' : 'No matching sections. Try “Apps Script”, “Client ID” or an error code.';
    matches.forEach(function (entry) {
      var link = document.createElement('a'); link.className = 'sm-search-result'; link.href = '#' + entry.id;
      var edition = document.createElement('span'); edition.className = 'sm-search-edition'; edition.textContent = entry.edition;
      var title = document.createElement('strong'); title.textContent = entry.title;
      var excerpt = document.createElement('small');
      var normalized = normalize(entry.content); var firstWord = normalize(query).split(' ')[0];
      var start = Math.max(0, normalized.indexOf(firstWord) - 30);
      excerpt.textContent = (start ? '…' : '') + entry.content.slice(start, start + 125) + (entry.content.length > start + 125 ? '…' : '');
      link.append(edition, title, excerpt);
      link.addEventListener('click', function () { revealHash('#' + entry.id); closeSearch(); if (mobile.matches) { menu.open = false; } });
      resultList.appendChild(link);
    });
    resultPanel.hidden = false; search.setAttribute('aria-expanded', 'true');
  }
  search.addEventListener('input', renderSearch);
  search.addEventListener('focus', function () { if (search.value.trim()) { renderSearch(); } });
  search.addEventListener('keydown', function (event) {
    if (event.key === 'ArrowDown' && !resultPanel.hidden) { var first = resultList.querySelector('a'); if (first) { event.preventDefault(); first.focus(); } }
    if (event.key === 'Enter' && !resultPanel.hidden) { var result = resultList.querySelector('a'); if (result) { event.preventDefault(); result.click(); } }
  });
  document.addEventListener('keydown', function (event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); search.focus(); search.select(); }
    if (event.key === 'Escape') { var active = document.activeElement; if (active && active.closest('.sm-search')) { search.focus(); } closeSearch(); }
  });
  document.addEventListener('click', function (event) { if (!event.target.closest('.sm-search')) { closeSearch(); } });

  function selectCode(element) {
    var selection = window.getSelection(); var range = document.createRange(); range.selectNodeContents(element);
    selection.removeAllRanges(); selection.addRange(range);
  }
  document.querySelectorAll('[data-copy]').forEach(function (button) {
    button.hidden = false;
    var timer;
    button.addEventListener('click', async function () {
      var code = document.getElementById(button.dataset.copy); if (!code) { return; }
      clearTimeout(timer); button.disabled = true;
      try {
        if (navigator.clipboard && window.isSecureContext) { await navigator.clipboard.writeText(code.textContent); }
        else { selectCode(code); if (!document.execCommand('copy')) { throw new Error('Clipboard unavailable'); } window.getSelection().removeAllRanges(); }
        button.textContent = 'Copied'; delete button.dataset.error; copyStatus.textContent = 'Code copied to clipboard.';
      } catch (error) {
        selectCode(code); button.textContent = 'Select & copy'; button.dataset.error = 'true'; copyStatus.textContent = 'Clipboard access is unavailable. The code is selected for manual copying.';
      } finally { button.disabled = false; timer = setTimeout(function () { button.textContent = button.closest('.sm-source-editor') ? 'Copy code' : 'Copy'; delete button.dataset.error; }, 2200); }
    });
  });

  var progress = document.getElementById('sm-progress-bar');
  var backTop = document.getElementById('sm-back-top');
  var scheduled = false;
  function updateReading() {
    scheduled = false;
    var total = document.documentElement.scrollHeight - window.innerHeight;
    progress.style.width = (total > 0 ? Math.min(100, Math.max(0, window.scrollY / total * 100)) : 0) + '%';
    backTop.hidden = window.scrollY < 600;
    var current = sections[0];
    sections.forEach(function (section) { if (section.getBoundingClientRect().top <= (window.innerWidth <= 600 ? 160 : 125)) { current = section; } });
    navLinks.forEach(function (link) { if (link.hash === '#' + current.id) { link.setAttribute('aria-current', 'location'); } else { link.removeAttribute('aria-current'); } });
  }
  function scheduleReading() { if (!scheduled) { scheduled = true; window.requestAnimationFrame(updateReading); } }
  window.addEventListener('scroll', scheduleReading, { passive: true });
  window.addEventListener('resize', scheduleReading);
  backTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }); });
  updateReading();
})();
