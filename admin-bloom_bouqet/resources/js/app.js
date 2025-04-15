import "./bootstrap";

document.addEventListener("DOMContentLoaded", () => {
    const cards = document.querySelectorAll(".card");

    cards.forEach((card) => {
        card.addEventListener("click", () => {
            // Remove active class from all cards
            cards.forEach((c) => c.classList.remove("active"));

            // Add active class to the clicked card
            card.classList.add("active");
        });
    });
});
