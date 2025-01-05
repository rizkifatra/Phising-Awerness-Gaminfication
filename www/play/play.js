document.querySelectorAll(".level-btn").forEach((button) => {
  button.addEventListener("click", () => {
    const level = button.getAttribute("data-level");
    alert(`You selected Level ${level}`);
  });
});
