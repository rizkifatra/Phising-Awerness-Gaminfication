// This is a JavaScript file

// DOM Elements
const form = document.getElementById("expense-form");
const expenseList = document.getElementById("expense-list");
const chartCanvas = document.getElementById("expense-chart");
const lineChartCanvas = document.getElementById("expense-line-chart");
const editModal = new bootstrap.Modal(
  document.getElementById("editExpenseModal")
);
const editForm = document.getElementById("edit-form");

// Global data
let expenses = JSON.parse(localStorage.getItem("expenses")) || [];
let editIndex = -1;

// Chart instances
let pieChart = null;
let lineChart = null;

// Show Toast Notification
function showToast(message) {
  const toastElement = document.getElementById("expense-toast");
  const toastMessage = document.getElementById("toast-message");

  toastMessage.textContent = message; // Set the message dynamically
  const toast = new bootstrap.Toast(toastElement); // Initialize the toast
  toast.show(); // Show the toast
}

// Render expenses
function renderExpenses() {
  expenseList.innerHTML = "";

  expenses.forEach((expense, index) => {
    const item = document.createElement("li");
    item.className = "list-group-item";

    item.innerHTML = `
            ${expense.category}: $${expense.amount} on ${expense.date}
            <span>${expense.description}</span>
            <div>
                <button class="btn btn-sm btn-warning" onclick="openEditModal(${index})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteExpense(${index})">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        `;
    expenseList.appendChild(item);
  });

  renderChart();
  renderLineChart();
}

// Render pie chart
function renderChart() {
  const categories = expenses.reduce((acc, expense) => {
    acc[expense.category] = (acc[expense.category] || 0) + expense.amount;
    return acc;
  }, {});

  const data = {
    labels: Object.keys(categories),
    datasets: [
      {
        data: Object.values(categories),
        backgroundColor: ["#007bff", "#28a745", "#dc3545", "#ffc107"],
      },
    ],
  };

  if (pieChart) {
    pieChart.destroy();
  }

  pieChart = new Chart(chartCanvas, {
    type: "pie",
    data,
    options: {
      responsive: true,
      maintainAspectRatio: true,
    },
  });
}

// Render line chart
function renderLineChart() {
  const sortedExpenses = expenses
    .slice()
    .sort((a, b) => new Date(a.date) - new Date(b.date));
  const dates = sortedExpenses.map((expense) => expense.date);
  const amounts = sortedExpenses.map((expense) => expense.amount);

  const lineData = {
    labels: dates,
    datasets: [
      {
        label: "Expenses Over Time",
        data: amounts,
        fill: false,
        borderColor: "#007bff",
        tension: 0.1,
      },
    ],
  };

  if (lineChart) {
    lineChart.destroy();
  }

  lineChart = new Chart(lineChartCanvas, {
    type: "line",
    data: lineData,
    options: {
      responsive: true,
      maintainAspectRatio: true,
    },
  });
}

// Open edit modal
function openEditModal(index) {
  editIndex = index;
  const expense = expenses[index];

  document.getElementById("edit-amount").value = expense.amount;
  document.getElementById("edit-date").value = expense.date;
  document.getElementById("edit-category").value = expense.category;
  document.getElementById("edit-description").value = expense.description;

  editModal.show();
}

// Save changes from the modal
editForm.addEventListener("submit", (e) => {
  e.preventDefault();

  const updatedExpense = {
    amount: parseFloat(document.getElementById("edit-amount").value),
    date: document.getElementById("edit-date").value,
    category: document.getElementById("edit-category").value,
    description: document.getElementById("edit-description").value,
  };

  if (editIndex !== -1) {
    expenses[editIndex] = updatedExpense;
    localStorage.setItem("expenses", JSON.stringify(expenses));
    renderExpenses();
    editModal.hide();

    // Show notification
    showToast("Expense updated successfully!");
  }
});

// Delete expense
function deleteExpense(index) {
  expenses.splice(index, 1);
  localStorage.setItem("expenses", JSON.stringify(expenses));
  renderExpenses();

  // Show notification
  showToast("Expense deleted successfully!");
}

// Add new expense
form.addEventListener("submit", (e) => {
  e.preventDefault();

  const newExpense = {
    amount: parseFloat(document.getElementById("amount").value),
    date: document.getElementById("date").value,
    category: document.getElementById("category").value,
    description: document.getElementById("description").value,
  };

  expenses.push(newExpense);
  localStorage.setItem("expenses", JSON.stringify(expenses));
  form.reset();
  renderExpenses();

  // Show notification
  showToast("Expense added successfully!");
});

// Initial render
renderExpenses();
