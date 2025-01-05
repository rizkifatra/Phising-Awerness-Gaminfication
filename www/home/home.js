// Redirect to the respective pages when buttons are clicked
document.getElementById("playGameButton").addEventListener("click", () => {
  window.location.href = "../play/play.html";
});

document.getElementById("leaderboardButton").addEventListener("click", () => {
  window.location.href = "leaderboard.html";
});

document.getElementById("viewProgressButton").addEventListener("click", () => {
  window.location.href = "view_progress.html";
});

document
  .getElementById("viewPerformanceButton")
  .addEventListener("click", () => {
    window.location.href = "view_performance.html";
  });
