const loginForm = document.getElementById("login-form");
const registerForm = document.getElementById("register-form");
registerForm.style.display = "none";
document.getElementById("newUser").addEventListener("click", function (e) {
  e.preventDefault();
  loginForm.style.display = "none";
  registerForm.style.display = "block";
  document
    .querySelectorAll("#login-modal .input-form input")
    .forEach((input) => {
      input.value = "";
    });
  insertGoogleLoginIntoDialog();
});
document.getElementById("haveUser").addEventListener("click", function (e) {
  e.preventDefault();
  registerForm.style.display = "none";
  loginForm.style.display = "block";
  document
    .querySelectorAll("#login-modal .input-form input")
    .forEach((input) => {
      input.value = "";
    });
  insertGoogleLoginIntoDialog();
});
const labels = document.querySelectorAll(".input-form label");
const inputs = document.querySelectorAll(".input-form input");
inputs.forEach((input, idx) => {
  input.addEventListener("focus", () => {
    labels[idx].style.top = "-8px";
    labels[idx].style.fontSize = "10px";
    labels[idx].style.color = "#0078d4";
  });
  input.addEventListener("blur", () => {
    if (!input.value) {
      labels[idx].style.top = "";
      labels[idx].style.fontSize = "16px";
      labels[idx].style.color = "#333";
    }
  });
});

(function () {
  const modal = document.getElementById("login-modal");
  const openButtons = Array.from(
    document.querySelectorAll(".btn-login")
  ).filter((b) => {
    const t = (b.textContent || "").trim().toLowerCase();
    return (
      t === "log in" ||
      t === "log in" ||
      t === "đăng nhập" ||
      t === "nhắn tin" ||
      t === "theo dõi" ||
      t.includes("log in") ||
      t.includes("đăng nhập") ||
      t.includes("Nhắn tin")
    );
  });
  const closeElements = modal.querySelectorAll('[data-action="close"]');

  function openModal() {
    modal.classList.add("active");
    modal.setAttribute("aria-hidden", "false");
    // focus first input for accessibility
    const first = modal.querySelector(
      'input[type="text"], input[type="email"], input:not([type])'
    );
    if (first) first.focus();
    document.body.style.overflow = "hidden";
  }
  function closeModal() {
    modal.classList.remove("active");
    modal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    document
      .querySelectorAll("#login-modal .input-form input")
      .forEach((input) => {
        input.value = "";
      });
  }

  openButtons.forEach((b) => b.addEventListener("click", openModal));
  closeElements.forEach((el) => el.addEventListener("click", closeModal));
  modal.addEventListener("click", (e) => {
    if (e.target === modal.querySelector(".overlay")) closeModal();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.classList.contains("active")) closeModal();
  });
})();
