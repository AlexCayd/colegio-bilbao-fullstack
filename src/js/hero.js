const heroPhrases = [
    "Learning to become yourself",
    "Tu voz, tu camino",
    "Think free. Act responsible.",
    "Naturaleza que despierta tu mente",
    "Belong. Explore. Build your future.",
    "Aquí tu voz importa."
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
