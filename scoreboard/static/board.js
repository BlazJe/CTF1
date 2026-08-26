(function () {
  "use strict";

  var state = window.__STATE__;
  var tasksEl = document.getElementById("tasks");
  var toastHost = document.getElementById("toast-host");

  // ---------------------------------------------------------------- pomožno

  function el(tag, cls, text) {
    var node = document.createElement(tag);
    if (cls) node.className = cls;
    if (text !== undefined && text !== null) node.textContent = text;
    return node;
  }

  function toast(message, kind) {
    var t = el("div", "toast toast-" + (kind || "info"), message);
    toastHost.appendChild(t);
    requestAnimationFrame(function () { t.classList.add("in"); });
    setTimeout(function () {
      t.classList.remove("in");
      setTimeout(function () { t.remove(); }, 260);
    }, 3600);
  }

  function post(url, body) {
    return fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body || {}),
    }).then(function (res) {
      return res.json().then(function (data) { return { status: res.status, data: data }; });
    });
  }

  // Vrednosti vnosnih polj preživijo ponovni izris.
  function captureInputs() {
    var saved = {};
    tasksEl.querySelectorAll("input[data-task]").forEach(function (input) {
      if (input.value) saved[input.dataset.task] = input.value;
    });
    return saved;
  }

  function restoreInputs(saved) {
    Object.keys(saved).forEach(function (id) {
      var input = tasksEl.querySelector('input[data-task="' + id + '"]');
      if (input) input.value = saved[id];
    });
  }

  // ---------------------------------------------------------------- kartice

  function lockedCard(task) {
    var card = el("article", "task task-locked");
    card.id = "task-" + task.id;

    var icon = el("div", "lock-icon");
    icon.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
      'stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" ' +
      'height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';

    var body = el("div", "locked-body");
    body.appendChild(el("strong", null, task.order + ". naloga"));
    body.appendChild(el("span", null, "Odklene se, ko rešiš " + (task.order - 1) + ". nalogo."));

    card.appendChild(icon);
    card.appendChild(body);
    return card;
  }

  function hintBlock(task) {
    var wrap = el("div", "hints");

    if (task.hint1_revealed) {
      var h1 = el("div", "hint hint-1");
      h1.appendChild(el("span", "hint-tag", "Namig 1"));

      if (task.tool) {
        var toolRow = el("div", "hint-tool");
        toolRow.appendChild(el("span", "hint-tool-label", "Orodje"));
        toolRow.appendChild(el("code", null, task.tool));
        h1.appendChild(toolRow);
      }

      h1.appendChild(el("p", null, task.hint1));
      wrap.appendChild(h1);
    }

    if (task.hint2_revealed) {
      var h2 = el("div", "hint hint-2");
      h2.appendChild(el("span", "hint-tag", "Namig 2 - rešitev"));
      var pre = el("pre", null, task.hint2);
      h2.appendChild(pre);
      wrap.appendChild(h2);
    }

    if (!task.solved) {
      var actions = el("div", "hint-actions");

      if (!task.hint1_revealed) {
        var b1 = el("button", "hint-btn", "Namig 1  (-" + task.hint1_cost + " točk)");
        b1.addEventListener("click", function () { revealHint(task, 1); });
        actions.appendChild(b1);
      }

      if (task.hint1_revealed && !task.hint2_revealed) {
        var b2 = el("button", "hint-btn hint-btn-danger", "Namig 2: pokaži rešitev");
        b2.addEventListener("click", function () { revealHint(task, 2); });
        actions.appendChild(b2);
        actions.appendChild(el("span", "hint-warning", "Naloga bo štela 0 točk."));
      } else if (!task.hint1_revealed) {
        actions.appendChild(el("span", "hint-warning muted",
          "Drugi namig se odklene po prvem."));
      }

      if (actions.childNodes.length) wrap.appendChild(actions);
    }

    return wrap;
  }

  function openCard(task) {
    var card = el("article", "task");
    card.id = "task-" + task.id;

    var head = el("header", "task-head");
    var num = el("div", "task-num", String(task.order));
    var titles = el("div", "task-titles");
    titles.appendChild(el("h3", null, task.title));
    titles.appendChild(el("span", "task-points", task.points + " točk"));
    head.appendChild(num);
    head.appendChild(titles);
    card.appendChild(head);

    card.appendChild(el("p", "task-desc", task.description));

    card.appendChild(hintBlock(task));

    var form = el("form", "answer");
    var input = el("input");
    input.type = "text";
    input.placeholder = "Vnesi odgovor";
    input.autocomplete = "off";
    input.spellcheck = false;
    input.dataset.task = String(task.id);

    var btn = el("button", "btn-check", "Preveri");
    btn.type = "submit";

    form.appendChild(input);
    form.appendChild(btn);
    form.addEventListener("submit", function (ev) {
      ev.preventDefault();
      submit(task, input, card);
    });

    card.appendChild(form);
    return card;
  }

  function solvedCard(task) {
    var card = el("article", "task task-solved");
    card.id = "task-" + task.id;

    var head = el("header", "task-head");
    var num = el("div", "task-num done");
    num.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" ' +
      'stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
    var titles = el("div", "task-titles");
    titles.appendChild(el("h3", null, task.title));

    var meta = el("span", "task-points");
    if (task.zeroed) {
      meta.className = "task-points zero";
      meta.textContent = "0 točk (uporabljen namig 2)";
    } else {
      meta.textContent = task.earned + " / " + task.points + " točk";
    }
    titles.appendChild(meta);

    head.appendChild(num);
    head.appendChild(titles);
    card.appendChild(head);

    var answer = el("div", "solved-answer");
    answer.appendChild(el("span", "solved-label", "Sprejeti odgovor"));
    answer.appendChild(el("code", null, task.answer));
    card.appendChild(answer);

    if (task.learn_url) {
      var learn = el("a", "learn");
      learn.href = task.learn_url;
      learn.target = "_blank";
      learn.rel = "noopener";
      learn.innerHTML =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
        'stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>' +
        '<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>';
      learn.appendChild(el("span", null, task.learn_label));
      card.appendChild(learn);
    }

    return card;
  }

  // ---------------------------------------------------------------- dejanja

  function revealHint(task, level) {
    if (level === 2) {
      var ok = window.confirm(
        "Drugi namig razkrije celotno rešitev.\n\n" +
        "Če ga odpreš, ta naloga ne bo prinesla nobenih točk. Nadaljujem?"
      );
      if (!ok) return;
    }

    post("/api/namig/" + task.id + "/" + level).then(function (res) {
      if (!res.data.ok) {
        toast(res.data.error || "Namiga ni bilo mogoče odpreti.", "error");
        return;
      }
      render(res.data.state);
      var card = document.getElementById("task-" + task.id);
      if (card) card.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }).catch(function () {
      toast("Povezava s strežnikom ni uspela.", "error");
    });
  }

  function submit(task, input, card) {
    var value = input.value.trim();
    if (!value) {
      input.focus();
      return;
    }

    post("/api/oddaja/" + task.id, { answer: value }).then(function (res) {
      if (!res.data.ok) {
        toast(res.data.error || "Oddaja ni uspela.", "error");
        return;
      }

      if (res.data.correct) {
        toast("Pravilno! Naslednja naloga je odklenjena.", "ok");
        render(res.data.state);
        var next = document.getElementById("task-" + (task.id + 1));
        var target = next || document.getElementById("task-" + task.id);
        if (target) {
          setTimeout(function () {
            target.scrollIntoView({ behavior: "smooth", block: "center" });
          }, 120);
        }
      } else {
        toast(res.data.message, "error");
        card.classList.remove("shake");
        void card.offsetWidth;
        card.classList.add("shake");
        input.select();
      }
    }).catch(function () {
      toast("Povezava s strežnikom ni uspela.", "error");
    });
  }

  // ---------------------------------------------------------------- izris

  function render(next) {
    state = next;

    document.getElementById("team-name").textContent = state.team;
    document.getElementById("score-value").textContent =
      state.score + " / " + state.max_score;

    document.getElementById("progress-percent").textContent = state.percent + "%";
    document.getElementById("progress-fill").style.width = state.percent + "%";
    document.getElementById("progress-sub").textContent =
      "Rešenih " + state.solved_count + " od " + state.total + " nalog.";

    var env = state.env;
    var url = document.getElementById("env-target-url");
    url.href = env.target_url;
    document.getElementById("env-target-url-text").textContent = env.target_url;
    document.getElementById("env-target-ip").textContent = env.target_ip;
    document.getElementById("env-host-ip").textContent = env.host_ip;

    var finish = document.getElementById("finish-panel");
    if (state.finished) {
      finish.hidden = false;
      document.getElementById("finish-text").textContent =
        "Ekipa " + state.team + " je rešila vseh " + state.total +
        " stopenj izziva Nordvel d.o.o. Čestitke!";
      document.getElementById("finish-score").textContent = state.score;
    } else {
      finish.hidden = true;
    }

    var saved = captureInputs();
    tasksEl.textContent = "";

    state.tasks.forEach(function (task) {
      if (!task.unlocked) {
        tasksEl.appendChild(lockedCard(task));
      } else if (task.solved) {
        tasksEl.appendChild(solvedCard(task));
      } else {
        tasksEl.appendChild(openCard(task));
      }
    });

    restoreInputs(saved);
  }

  // ------------------------------------------------------------ kopiranje

  document.addEventListener("click", function (ev) {
    var btn = ev.target.closest("[data-copy-target]");
    if (!btn) return;
    var node = document.getElementById(btn.dataset.copyTarget);
    if (!node) return;

    var text = node.textContent;
    var done = function () { toast("Kopirano: " + text, "ok"); };

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done, function () { done(); });
    } else {
      var tmp = document.createElement("textarea");
      tmp.value = text;
      tmp.style.position = "fixed";
      tmp.style.opacity = "0";
      document.body.appendChild(tmp);
      tmp.select();
      try { document.execCommand("copy"); } catch (e) { /* ni bistveno */ }
      tmp.remove();
      done();
    }
  });

  render(state);
})();
