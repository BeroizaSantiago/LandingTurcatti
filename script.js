const WHATSAPP_NUMBER = "5492944691270";
const WHATSAPP_MESSAGE = "Hola Dalila, quisiera realizar una consulta legal.";

const header = document.querySelector(".site-header");
const toggle = document.querySelector(".nav-toggle");
const navigation = document.querySelector(".main-nav");
const form = document.querySelector(".contact-form");
const statusBox = document.querySelector(".form-status");

document.querySelectorAll(".js-whatsapp").forEach((link) => {
  link.href = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(WHATSAPP_MESSAGE)}`;
});

document.querySelector("#year").textContent = new Date().getFullYear();
document.querySelector("#form_started").value = Math.floor(Date.now() / 1000);

window.addEventListener("scroll", () => {
  header.classList.toggle("scrolled", window.scrollY > 30);
});

toggle.addEventListener("click", () => {
  const isOpen = navigation.classList.toggle("open");
  toggle.classList.toggle("active", isOpen);
  toggle.setAttribute("aria-expanded", String(isOpen));
  document.body.classList.toggle("menu-open", isOpen);
});

navigation.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", () => {
    navigation.classList.remove("open");
    toggle.classList.remove("active");
    toggle.setAttribute("aria-expanded", "false");
    document.body.classList.remove("menu-open");
  });
});

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add("visible");
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll(".reveal").forEach((element) => observer.observe(element));

const messages = {
  valueMissing: "Este campo es obligatorio.",
  typeMismatch: "Ingresá un email válido.",
  tooShort: "La información ingresada es demasiado corta."
};

function validateField(field) {
  const wrapper = field.closest(".field");
  if (!wrapper) return field.checkValidity();

  const error = wrapper.querySelector(".field-error");
  const valid = field.checkValidity();
  wrapper.classList.toggle("invalid", !valid);

  if (valid) {
    error.textContent = "";
  } else if (field.validity.valueMissing) {
    error.textContent = messages.valueMissing;
  } else if (field.validity.typeMismatch) {
    error.textContent = messages.typeMismatch;
  } else if (field.validity.tooShort) {
    error.textContent = messages.tooShort;
  } else {
    error.textContent = "Revisá este campo.";
  }

  return valid;
}

form.querySelectorAll("input, select, textarea").forEach((field) => {
  if (field.type !== "checkbox" && field.name !== "website") {
    field.addEventListener("blur", () => validateField(field));
    field.addEventListener("input", () => {
      if (field.closest(".field")?.classList.contains("invalid")) validateField(field);
    });
  }
});

form.addEventListener("submit", async (event) => {
  event.preventDefault();
  statusBox.className = "form-status";
  statusBox.textContent = "";

  const requiredFields = [...form.querySelectorAll("[required]")];
  const fieldsValid = requiredFields
    .filter((field) => field.type !== "checkbox")
    .every(validateField);
  const privacy = form.querySelector("[name='privacy']");

  if (!fieldsValid || !privacy.checked) {
    statusBox.className = "form-status error";
    statusBox.textContent = "Revisá los campos obligatorios antes de enviar.";
    return;
  }

  const button = form.querySelector(".submit-button");
  const buttonText = button.querySelector("span");
  button.disabled = true;
  buttonText.textContent = "Enviando...";

  try {
    const response = await fetch(form.action, {
      method: "POST",
      body: new FormData(form),
      headers: { "X-Requested-With": "XMLHttpRequest" }
    });
    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(result.message || "No pudimos enviar tu consulta.");
    }

    statusBox.className = "form-status success";
    statusBox.textContent = result.message;
    form.reset();
    document.querySelector("#form_started").value = Math.floor(Date.now() / 1000);
  } catch (error) {
    statusBox.className = "form-status error";
    statusBox.textContent = error.message || "Ocurrió un error. Intentá nuevamente o escribinos por WhatsApp.";
  } finally {
    button.disabled = false;
    buttonText.textContent = "Enviar consulta";
  }
});
