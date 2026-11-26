const API_HORARIOS = "../backend/horarios.php";
const ARCHIVO_HORARIOS = "../js/horarios-publicos.json";

const cursoSelect = document.getElementById("cursoSelect");
const horariosBody = document.getElementById("horariosBody");
const infoCurso = document.getElementById("infoCurso");

cargarHorarios();

async function cargarHorarios() {
  let cursos;
  let aviso = "";

  try {
    try {
      const respuesta = await fetch(API_HORARIOS, { cache: "no-store" });
      if (!respuesta.ok) {
        const resultado = await respuesta.json().catch(() => ({}));
        throw new Error(resultado.error || "No se pudieron consultar los horarios guardados.");
      }

      const resultado = await respuesta.json();
      cursos = resultado.cursos;
      if (!Array.isArray(cursos) || cursos.length === 0) {
        throw new Error("No hay horarios guardados.");
      }
    } catch (error) {
      console.warn("No se pudo consultar la base de datos; se cargarán los horarios locales.", error);
      const respuestaLocal = await fetch(ARCHIVO_HORARIOS);
      if (!respuestaLocal.ok) {
        throw new Error("No se encontraron los horarios guardados ni el archivo local.");
      }
      cursos = await respuestaLocal.json();
      aviso = "Mostrando la versión de respaldo; los cambios guardados en la base no están disponibles.";
    }

    if (!Array.isArray(cursos) || cursos.length === 0) {
      throw new Error("No hay horarios disponibles.");
    }

    cursoSelect.replaceChildren();
    cursos.forEach((curso, indice) => {
      const opcion = document.createElement("option");
      opcion.value = String(indice);
      opcion.textContent = curso.course;
      cursoSelect.appendChild(opcion);
    });

    cursoSelect.addEventListener("change", () => {
      mostrarHorario(cursos[Number(cursoSelect.value)], aviso);
    });

    mostrarHorario(cursos[0], aviso);
  } catch (error) {
    console.error(error);
    const mensaje = document.createElement("strong");
    mensaje.textContent = "No se pudieron cargar los horarios.";
    infoCurso.replaceChildren(mensaje);
  }
}

function mostrarHorario(curso, aviso = "") {
  if (!curso) {
    return;
  }

  const titulo = document.createElement("strong");
  titulo.textContent = curso.course;
  const elementos = [titulo];
  if (aviso) {
    const nota = document.createElement("span");
    nota.className = "detalle";
    nota.textContent = aviso;
    elementos.push(nota);
  }
  infoCurso.replaceChildren(...elementos);
  horariosBody.replaceChildren();

  curso.schedule.forEach((bloque) => {
    const fila = document.createElement("tr");
    const celdaHora = document.createElement("td");
    celdaHora.className = "hora";
    celdaHora.textContent = bloque.time;
    fila.appendChild(celdaHora);

    bloque.days.forEach((asignatura) => {
      const celda = document.createElement("td");
      const contenido = document.createElement("span");
      contenido.className = asignatura ? "materia" : "vacio";
      contenido.textContent = asignatura || "—";
      celda.appendChild(contenido);
      fila.appendChild(celda);
    });

    horariosBody.appendChild(fila);
  });
}
