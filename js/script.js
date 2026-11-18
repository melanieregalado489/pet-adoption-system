// PawHeart · maliliit na effects lang. Ang totoong logic ay nasa PHP/MySQL na.
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

// ✨ Sumusulpot pag-scroll
const io = new IntersectionObserver(
  (entries) =>
    entries.forEach((e) => {
      if (e.isIntersecting) {
        e.target.classList.add("in");
        io.unobserve(e.target);
      }
    }),
  { threshold: 0.1 },
);
$$(".card,.stat,.app,h2,.cats a,.hero,.detail").forEach((e, i) => {
  e.classList.add("rv");
  e.style.setProperty("--d", (i % 6) * 80 + "ms");
  io.observe(e);
});

// 🐾 Lumulutang na background
if (!matchMedia("(prefers-reduced-motion: reduce)").matches) {
  const f = document.createElement("div");
  f.id = "float";
  f.setAttribute("aria-hidden", "true");
  ["🐾", "🐶", "🐱", "🐾", "🦴", "🐾", "🐟", "🐾"].forEach((c, i) => {
    const s = document.createElement("span");
    s.textContent = c;
    s.style.left = 6 + i * 12 + "%";
    s.style.fontSize = 18 + ((i * 7) % 18) + "px";
    s.style.animationDuration = 14 + ((i * 5) % 12) + "s";
    s.style.animationDelay = -i * 3 + "s";
    f.append(s);
  });
  document.body.prepend(f);
}

// 🪪 Preview + basic check ng ID bago i-submit (nasa server pa rin ang totoong validation)
const idInput = $("#id_file");
if (idInput) {
  const box = $("#idpreview");
  idInput.addEventListener("change", () => {
    const f = idInput.files[0];
    box.hidden = !f;
    box.textContent = "";
    if (!f) return;
    if (f.size > 5 * 1024 * 1024) {
      idInput.value = "";
      box.hidden = false;
      box.textContent = "That file is over 5 MB. Please choose a smaller one.";
      box.classList.add("bad");
      return;
    }
    box.classList.remove("bad");
    if (f.type.startsWith("image/")) {
      const img = document.createElement("img");
      img.src = URL.createObjectURL(f);
      img.alt = "Preview of your ID";
      box.append(img);
    } else {
      box.textContent = "📄 " + f.name;
    }
  });
}
