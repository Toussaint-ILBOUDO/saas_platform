"use strict";

(function () {
  const sidebarKey = "adminHMD.sidebarMini";
  const themeKey = "adminHMD.theme";
  const desktopQuery = "(min-width: 992px)";

  function ready(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  function isDesktop() {
    return window.matchMedia(desktopQuery).matches;
  }

  function canUseStorage() {
    try {
      localStorage.setItem("__test", "1");
      localStorage.removeItem("__test");
      return true;
    } catch {
      return false;
    }
  }

  function getSidebarState() {
    return localStorage.getItem(sidebarKey) === "true";
  }

  function setSidebarState(val) {
    localStorage.setItem(sidebarKey, val ? "true" : "false");
  }

  function getTheme() {
    const saved = localStorage.getItem(themeKey);
    if (saved === "dark" || saved === "light") return saved;

    return window.matchMedia("(prefers-color-scheme: dark)").matches
      ? "dark"
      : "light";
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    document.documentElement.setAttribute("data-bs-theme", theme);
    localStorage.setItem(themeKey, theme);
  }

  function initUserProfile() {
    const user = window.adminHMDUser;

    if (!user) return;

    if (user.name) {
      document.querySelectorAll(".profile-name").forEach(el => {
        el.textContent = user.name;
      });

      document.querySelectorAll(".sidebar-user strong").forEach(el => {
        el.textContent = user.name;
      });
    }

    if (user.workspace) {
      document.querySelectorAll(".sidebar-user small").forEach(el => {
        el.textContent = user.workspace;
      });
    }

    if (user.avatar) {
      document.querySelectorAll(".avatar-img").forEach(img => {
        img.src = user.avatar;
        img.alt = user.name || "User";
      });
    }
  }

  ready(function () {
    const body = document.body;
    const toggle = document.querySelector("[data-sidebar-toggle]");
    const closeEls = document.querySelectorAll("[data-sidebar-close]");
    const links = document.querySelectorAll(".sidebar-nav .nav-link");

    const storage = canUseStorage();

    // THEME INIT
    applyTheme(getTheme());

    document.querySelectorAll("[data-theme-toggle]").forEach(btn => {
      btn.addEventListener("click", () => {
        const current = document.documentElement.getAttribute("data-theme");
        applyTheme(current === "dark" ? "light" : "dark");
      });
    });

    // USER SAFE INIT (NO CRASH POSSIBLE)
    initUserProfile();

    // SIDEBAR INIT
    if (storage && getSidebarState() && isDesktop()) {
      body.classList.add("sidebar-mini");
    }

    function updateToggleState() {
      if (!toggle) return;

      const expanded = isDesktop()
        ? !body.classList.contains("sidebar-mini")
        : body.classList.contains("sidebar-open");

      toggle.setAttribute("aria-expanded", String(expanded));
    }

    function openCloseSidebar() {
      if (isDesktop()) {
        body.classList.toggle("sidebar-mini");
        setSidebarState(body.classList.contains("sidebar-mini"));
      } else {
        body.classList.toggle("sidebar-open");
      }

      updateToggleState();
    }

    function closeMobile() {
      body.classList.remove("sidebar-open");
      updateToggleState();
    }

    if (toggle) {
      toggle.addEventListener("click", openCloseSidebar);
    }

    closeEls.forEach(el =>
      el.addEventListener("click", () => {
        if (!isDesktop()) closeMobile();
      })
    );

    links.forEach(el =>
      el.addEventListener("click", () => {
        if (!isDesktop()) closeMobile();
      })
    );

    updateToggleState();
  });
})();