/**
 * Renders the one-time "save your backup codes" reveal modal used across the
 * Two-Factor profile setup, status and regenerate flows. Codes are only ever
 * available in-memory on the client at the moment they are generated, so
 * Copy/Download both work directly off the array passed in here rather than
 * fetching anything from the server again.
 */
(function () {
  "use strict";

  const modalOpenClass = "sucuriscan-backup-codes-modal-open";
  const overlaySelector = ".sucuriscan-backup-codes-overlay";

  const createElement = (tagName, options = {}) => {
    const element = document.createElement(tagName);

    if (options.className) {
      element.className = options.className;
    }

    if (options.text) {
      element.textContent = options.text;
    }

    Object.entries(options.attributes || {}).forEach(([name, value]) => {
      element.setAttribute(name, String(value));
    });

    return element;
  };

  const writeClipboard = async (text) => {
    if (!navigator.clipboard?.writeText) {
      return false;
    }

    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch (error) {
      return false;
    }
  };

  const setPageScrollLocked = (locked) => {
    document.documentElement.classList.toggle(modalOpenClass, locked);
    document.body.classList.toggle(modalOpenClass, locked);
  };

  const buildCodeList = (codes) => {
    const list = createElement("ul", {
      className: "sucuriscan-backup-codes-list",
    });

    codes.forEach((code) => {
      const item = document.createElement("li");
      const value = createElement("code", { text: code });

      item.append(value);
      list.append(item);
    });

    return list;
  };

  const buildModal = (codes, onClose) => {
    const text = codes.join("\n");
    const overlay = createElement("div", {
      className: "sucuriscan-backup-codes-overlay",
      attributes: {
        role: "dialog",
        "aria-label": "Save your backup codes",
        "aria-modal": "true",
      },
    });
    const modal = createElement("div", {
      className: "sucuriscan-backup-codes-modal",
    });
    const title = createElement("h2", { text: "Save your backup codes" });
    const description = createElement("p", {
      text: "Each code can be used once to sign in if you lose access to your authenticator app. Store them somewhere safe - they will not be shown again.",
    });
    const actions = createElement("div", {
      className: "sucuriscan-backup-codes-actions",
    });
    const copyButton = createElement("button", {
      className: "button",
      text: "Copy codes",
      attributes: { type: "button" },
    });
    const downloadButton = createElement("button", {
      className: "button",
      text: "Download .txt",
      attributes: { type: "button" },
    });
    const acknowledgement = createElement("label", {
      className: "sucuriscan-backup-codes-ack",
    });
    const acknowledgementCheckbox = createElement("input", {
      attributes: { type: "checkbox" },
    });
    const closeAction = createElement("div", {
      className: "sucuriscan-2fa-action-row",
    });
    const closeButton = createElement("button", {
      className: "button button-primary",
      text: "Close",
      attributes: { type: "button", disabled: "disabled" },
    });

    actions.append(copyButton, downloadButton);
    acknowledgement.append(
      acknowledgementCheckbox,
      " I have saved these codes",
    );
    closeAction.append(closeButton);
    modal.append(
      title,
      description,
      buildCodeList(codes),
      actions,
      acknowledgement,
      closeAction,
    );
    overlay.append(modal);
    document.body.append(overlay);
    setPageScrollLocked(true);
    copyButton.focus();

    acknowledgementCheckbox.addEventListener("change", () => {
      closeButton.disabled = !acknowledgementCheckbox.checked;
    });

    copyButton.addEventListener("click", async () => {
      copyButton.disabled = true;
      const copied = await writeClipboard(text);

      copyButton.textContent = copied ? "Copied!" : "Copy unavailable";

      window.setTimeout(() => {
        copyButton.textContent = "Copy codes";
        copyButton.disabled = false;
      }, 1500);
    });

    downloadButton.addEventListener("click", () => {
      if (typeof window.sucuriscanDownloadTextFile === "function") {
        window.sucuriscanDownloadTextFile(
          "sucuri-backup-codes.txt",
          `${text}\n`,
        );
      }
    });

    const confirmBeforeUnload = (event) => {
      if (!acknowledgementCheckbox.checked) {
        event.preventDefault();
      }
    };

    window.addEventListener("beforeunload", confirmBeforeUnload);

    closeButton.addEventListener("click", () => {
      window.removeEventListener("beforeunload", confirmBeforeUnload);
      overlay.remove();

      if (!document.querySelector(overlaySelector)) {
        setPageScrollLocked(false);
      }

      if (typeof onClose === "function") {
        onClose();
      }
    });
  };

  /**
   * @param {string[]} codes
   * @param {Function} [onClose] Called after the user closes the modal
   *   (e.g. to defer a page reload/redirect until the codes were shown).
   */
  window.sucuriShowBackupCodesModal = (codes, onClose) => {
    const validCodes = Array.isArray(codes)
      ? codes.filter((code) => typeof code === "string" && code !== "")
      : [];

    if (!validCodes.length) {
      if (typeof onClose === "function") {
        onClose();
      }

      return;
    }

    buildModal(validCodes, onClose);
  };

  const revealData = document.getElementById(
    "sucuriscan-backup-codes-reveal-data",
  );

  if (revealData) {
    let codes = [];
    let redirectURL = "";

    try {
      codes = JSON.parse(revealData.dataset.codes || "[]");
      redirectURL = JSON.parse(revealData.dataset.redirectUrl || '""');
    } catch (error) {
      codes = [];
      redirectURL = "";
    }

    window.sucuriShowBackupCodesModal(codes, () => {
      if (redirectURL) {
        window.location.assign(redirectURL);
      }
    });
  }
})();
