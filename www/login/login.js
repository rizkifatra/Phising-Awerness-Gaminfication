document.getElementById("loginButton").addEventListener("click", async () => {
  const email = document.getElementById("login-email").value;
  const password = document.getElementById("login-password").value;

  if (!email || !password) {
    document.getElementById("login-error-message").textContent =
      "Please fill in all fields.";
    return;
  }

  try {
    const response = await fetch("http://localhost:8080/api/login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password }),
    });

    if (response.ok) {
      alert("Login successful!");
    } else {
      document.getElementById("login-error-message").textContent =
        "Invalid credentials.";
    }
  } catch (error) {
    console.error(error);
    document.getElementById("login-error-message").textContent =
      "An error occurred.";
  }
});

// Handle Google Login
function handleGoogleLogin(response) {
  console.log("Google Token:", response.credential);

  fetch("http://localhost:8080/api/google-login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ token: response.credential }),
  })
    .then((res) => res.json())
    .then((data) => {
      alert("Google Login successful!");
      console.log(data);
    })
    .catch((error) => {
      console.error("Google Login error:", error);
    });
}
