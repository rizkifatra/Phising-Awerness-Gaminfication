// signup-script.js

const togglePassword = document.querySelector(".toggle-password");
const passwordInput = document.querySelector("#password");

togglePassword.addEventListener("click", () => {
  const type =
    passwordInput.getAttribute("type") === "password" ? "text" : "password";
  passwordInput.setAttribute("type", type);
});
