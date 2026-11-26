const API_ROOT = "../backend";
const loginPanel = document.getElementById("loginPanel");
const loginForm = document.getElementById("loginForm");
const loginError = document.getElementById("loginError");
const editorPanel = document.getElementById("editorPanel");
const pageMessage = document.getElementById("pageMessage");
const courseSelect = document.getElementById("courseSelect");
const courseName = document.getElementById("courseName");
const scheduleRows = document.getElementById("scheduleRows");
const saveButton = document.getElementById("saveButton");

let csrfToken = "";
let courses = [];
let currentCourseIndex = 0;

async function request(url, options = {}) {
  const headers = new Headers(options.headers || {});
  if (options.body) {
    headers.set("Content-Type", "application/json");
  }
  if (options.method && options.method !== "GET" && csrfToken) {
    headers.set("X-CSRF-Token", csrfToken);
  }

  let response;
  try {
    response = await fetch(url, { ...options, headers, credentials: "same-origin" });
  } catch {
    throw new Error("No se pudo conectar con el servidor.");
  }

  let result;
  try {
    result = await response.json();
  } catch {
    throw new Error("El servidor devolvió una respuesta inválida.");
  }
  if (!response.ok) {
    throw new Error(result.error || "No se pudo completar la operación.");
  }
  return result;
}

function setMessage(message, kind = "") {
  pageMessage.textContent = message;
  if (kind) {
    pageMessage.dataset.kind = kind;
  } else {
    delete pageMessage.dataset.kind;
  }
}

function showLogin(message = "") {
  csrfToken = "";
  editorPanel.hidden = true;
  loginPanel.hidden = false;
  loginError.textContent = message;
  loginError.hidden = !message;
}

function showEditor(state) {
  csrfToken = state.csrfToken || "";
  loginPanel.hidden = true;
  editorPanel.hidden = false;
  document.getElementById("signedInUser").textContent = `Sesión iniciada: ${state.user.name}`;
  setMessage("Cargando horarios...");
}

function renderCourseOptions() {
  const options = document.createDocumentFragment();
  courses.forEach((course, index) => {
    const option = document.createElement("option");
    option.value = String(index);
    option.textContent = course.course;
    options.appendChild(option);
  });
  courseSelect.replaceChildren(options);
  courseSelect.value = String(currentCourseIndex);
  document.getElementById("removeCourseButton").disabled = courses.length <= 1;
}

function captureCourse() {
  const course = courses[currentCourseIndex];
  if (!course) return;

  course.course = courseName.value;
  course.schedule = Array.from(scheduleRows.querySelectorAll("tr"), (row) => ({
    time: row.querySelector("[data-field='time']").value,
    days: Array.from(row.querySelectorAll("[data-field='day']"), (input) => input.value),
  }));
  const option = courseSelect.options[currentCourseIndex];
  if (option) option.textContent = course.course || "(Sin nombre)";
}

function makeEditorInput(value, field, label) {
  const input = document.createElement("input");
  input.type = "text";
  input.value = value || "";
  input.maxLength = field === "time" ? 40 : 120;
  input.dataset.field = field;
  input.setAttribute("aria-label", label);
  input.addEventListener("input", captureCourse);
  return input;
}

function renderCourse() {
  const course = courses[currentCourseIndex];
  if (!course) return;

  courseName.value = course.course;
  scheduleRows.replaceChildren();
  course.schedule.forEach((block, blockIndex) => {
    const row = document.createElement("tr");
    const timeCell = document.createElement("td");
    timeCell.appendChild(makeEditorInput(block.time, "time", "Horario"));
    row.appendChild(timeCell);

    block.days.forEach((day, dayIndex) => {
      const cell = document.createElement("td");
      cell.appendChild(makeEditorInput(day, "day", `Materia del día ${dayIndex + 1}`));
      row.appendChild(cell);
    });

    const actionCell = document.createElement("td");
    const removeButton = document.createElement("button");
    removeButton.type = "button";
    removeButton.className = "schedule-editor-button danger";
    removeButton.textContent = "Quitar";
    removeButton.setAttribute("aria-label", `Quitar bloque ${blockIndex + 1}`);
    removeButton.addEventListener("click", () => {
      captureCourse();
      courses[currentCourseIndex].schedule.splice(blockIndex, 1);
      renderCourse();
    });
    actionCell.appendChild(removeButton);
    row.appendChild(actionCell);
    scheduleRows.appendChild(row);
  });
}

async function loadSchedules() {
  const result = await request(`${API_ROOT}/horarios.php`);
  courses = result.cursos;
  currentCourseIndex = 0;
  renderCourseOptions();
  renderCourse();
  setMessage("Podés editar los horarios. Los cambios se publican al guardarlos.");
}

async function checkSession() {
  try {
    const state = await request(`${API_ROOT}/auth.php?action=status`);
    if (state.authenticated) {
      showEditor(state);
      await loadSchedules();
    } else {
      showLogin();
      setMessage("Ingresá con una cuenta autorizada para continuar.");
    }
  } catch (error) {
    showLogin();
    setMessage(`${error.message} Revisá la configuración del servidor.`, "error");
  }
}

loginForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  const submitButton = loginForm.querySelector("button[type='submit']");
  submitButton.disabled = true;
  loginError.hidden = true;
  try {
    const state = await request(`${API_ROOT}/auth.php?action=login`, {
      method: "POST",
      body: JSON.stringify({
        username: document.getElementById("username").value.trim(),
        password: document.getElementById("password").value,
      }),
    });
    loginForm.reset();
    showEditor(state);
    await loadSchedules();
  } catch (error) {
    if (editorPanel.hidden) {
      loginError.textContent = error.message;
      loginError.hidden = false;
    } else {
      setMessage(error.message, "error");
    }
  } finally {
    submitButton.disabled = false;
  }
});

courseSelect.addEventListener("change", () => {
  captureCourse();
  currentCourseIndex = Number(courseSelect.value);
  renderCourse();
});

courseName.addEventListener("input", captureCourse);

document.getElementById("addCourseButton").addEventListener("click", () => {
  captureCourse();
  courses.push({
    course: "Nuevo curso",
    schedule: [{ time: "", days: ["", "", "", "", ""] }],
  });
  currentCourseIndex = courses.length - 1;
  renderCourseOptions();
  renderCourse();
  courseName.focus();
  courseName.select();
});

document.getElementById("removeCourseButton").addEventListener("click", () => {
  const course = courses[currentCourseIndex];
  if (!course || courses.length <= 1 || !window.confirm(`¿Eliminar el horario de ${course.course}?`)) return;

  courses.splice(currentCourseIndex, 1);
  currentCourseIndex = Math.min(currentCourseIndex, courses.length - 1);
  renderCourseOptions();
  renderCourse();
});

document.getElementById("addBlockButton").addEventListener("click", () => {
  captureCourse();
  courses[currentCourseIndex].schedule.push({ time: "", days: ["", "", "", "", ""] });
  renderCourse();
});

saveButton.addEventListener("click", async () => {
  captureCourse();
  saveButton.disabled = true;
  setMessage("Guardando cambios...");
  try {
    const result = await request(`${API_ROOT}/horarios.php`, {
      method: "PUT",
      body: JSON.stringify({ cursos: courses }),
    });
    courses = result.cursos;
    currentCourseIndex = Math.min(currentCourseIndex, courses.length - 1);
    renderCourseOptions();
    renderCourse();
    setMessage("Horarios guardados y publicados para todas las personas.");
  } catch (error) {
    setMessage(error.message, "error");
  } finally {
    saveButton.disabled = false;
  }
});

document.getElementById("logoutButton").addEventListener("click", async () => {
  try {
    await request(`${API_ROOT}/auth.php?action=logout`, { method: "POST", body: "{}" });
    showLogin();
    setMessage("Sesión cerrada.");
  } catch (error) {
    setMessage(error.message, "error");
  }
});

checkSession();
