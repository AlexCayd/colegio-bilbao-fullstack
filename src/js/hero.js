const heroPhrases = [
    "Aprende a ser quien eres",
    "Pertenece. Explora. Construye tu futuro",
    "Piensa libremente. Crea con libertad",
    "Tu lugar para cuestionar el mundo",
    "Raíces profundas. Horizonte abierto"
];

const titleElement = document.getElementById('dynamic-hero-title');
let phraseIndex = 0;

function changePhrase() {
    if (!titleElement) return;

    titleElement.style.transition = "all 0.8s cubic-bezier(0.2, 0.8, 0.2, 1)";
    titleElement.style.opacity = "0";
    titleElement.style.transform = "rotateX(90deg)";

    setTimeout(() => {
        phraseIndex = (phraseIndex + 1) % heroPhrases.length;
        titleElement.textContent = heroPhrases[phraseIndex];

        titleElement.style.transition = "none";
        titleElement.style.transform = "rotateX(-90deg)";

        void titleElement.offsetWidth;

        titleElement.style.transition = "all 0.8s cubic-bezier(0.2, 0.8, 0.2, 1)";
        titleElement.style.opacity = "1";
        titleElement.style.transform = "rotateX(0deg)";
    }, 800);
}

if (titleElement) setInterval(changePhrase, 7000);
