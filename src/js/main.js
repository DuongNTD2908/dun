document.addEventListener("DOMContentLoaded", () => {
  const mainPost = document.getElementById("post");
  const postController = document.querySelector(".post-controller");
  postController.style.display = "none";

  const postItem = document.getElementById("btn-post");

  document.querySelectorAll(".tab").forEach((t) => {
    t.addEventListener("click", () => {
      document
        .querySelectorAll(".tab")
        .forEach((x) => x.classList.remove("active"));
      t.classList.add("active");
    });
  });

  mainPost.addEventListener("mouseenter", () => {
    postController.style.display = "flex";
  });
  postController.addEventListener("mouseleave", () => {
    postController.style.display = "none";
  });

  const notifBtn = document.getElementById("notif");
  const notificationNav = document.getElementById("notification-nav");
  notifBtn.addEventListener("mouseenter", () => {
    notificationNav.style.display = "block";
  });
  notificationNav.addEventListener("mouseenter", () => {
    notificationNav.style.display = "block";
  });
  notificationNav.addEventListener("mouseleave", () => {
    notificationNav.style.display = "none";
  });

  document.querySelector("body").addEventListener("click", () => {
    postController.style.display = "none";
    notificationNav.style.display = "none";
  });

  postItem.addEventListener("mouseenter", () => {
    postItem.style.background = "#6b94eeff";
    postItem.style.color = "#fff";
  });
  postItem.addEventListener("mouseleave", () => {
    postItem.style.background = "";
    postItem.style.color = "";
  });
});
