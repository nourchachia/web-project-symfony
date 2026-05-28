document.addEventListener("DOMContentLoaded", () => {

    // ─────────────────────────────────────────────
    // Utilitaires SweetAlert2
    // ─────────────────────────────────────────────

    const swalBase = {
        background: "#0a0a0a",
        color: "#fff",
    };

    const swalConfirm = (action) =>
        Swal.fire({
            ...swalBase,
            title: "Are you sure?",
            text: action === "accept"
                ? "This user will be accepted"
                : "This request will be declined",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "Cancel",
            confirmButtonColor: "#c0c0c0",
            cancelButtonColor: "#444",
        });

    const swalSuccess = () =>
        Swal.fire({
            ...swalBase,
            title: "Done",
            text: "Action completed",
            icon: "success",
            timer: 1200,
            showConfirmButton: false,
        });

    const swalError = (message = "Unknown error") =>
        Swal.fire({
            ...swalBase,
            title: "Error",
            text: message,
            icon: "error",
        });

    // ─────────────────────────────────────────────
    // Badge counter
    // ─────────────────────────────────────────────

    const updateBadge = () => {
        const badge = document.querySelector("#pending-badge");
        if (!badge) return;

        const count = document.querySelectorAll(".card:not(.empty-card)").length;
        badge.textContent = `${count} request${count !== 1 ? "s" : ""} pending`;
    };

    // ─────────────────────────────────────────────
    // Empty state card
    // ─────────────────────────────────────────────

    const injectEmptyCard = (container) => {
        if (container.querySelector(".empty-card")) return;

        const emptyCard = document.createElement("div");
        emptyCard.className = "card empty-card";
        emptyCard.style.cssText = "opacity:0; transition:opacity 0.4s ease;";
        emptyCard.innerHTML = `
            <div class="card-content" style="text-align:center; padding: 20px 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                    fill="none" stroke="rgba(192,192,192,0.4)" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round"
                    style="display:block; margin: 0 auto 20px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="9" y1="13" x2="15" y2="13"/>
                </svg>
                <span class="label" style="display:block; margin-bottom:10px; font-size:11px; letter-spacing:2px;">
                    Aucune demande
                </span>
                <span class="value" style="font-size:17px; color:rgba(200,200,200,0.6);">
                    Pas de demande d'ajout
                </span>
            </div>
        `;

        container.appendChild(emptyCard);
        requestAnimationFrame(() => { emptyCard.style.opacity = "1"; });
    };

    // ─────────────────────────────────────────────
    // Suppression animée d'une carte
    // ─────────────────────────────────────────────

    const removeCard = (card) => {
        card.style.cssText += "transition:opacity 0.3s, transform 0.3s; opacity:0; transform:scale(0.8);";

        setTimeout(() => {
            const container = card.closest(".card-container");
            card.remove();
            updateBadge();

            if (container && !container.querySelector(".card:not(.empty-card)")) {
                injectEmptyCard(container);
            }
        }, 300);
    };

    // ─────────────────────────────────────────────
    // Appel API
    // ─────────────────────────────────────────────

    const handleApproval = async (id, action) => {
        const res = await fetch("/approvals/handle", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id, action }),
        });

        const text = await res.text();

        try {
            return JSON.parse(text);
        } catch {
            console.error("❌ Réponse non-JSON :", text);
            throw new Error("Invalid server response");
        }
    };

    // ─────────────────────────────────────────────
    // Délégation d'événement — fonctionne pour les
    // cartes existantes ET injectées dynamiquement
    // ─────────────────────────────────────────────

    document.addEventListener("click", async (e) => {
        const btn = e.target.closest(".btn");
        if (!btn) return;

        const card = btn.closest(".card");
        const id   = card?.dataset?.id;

        if (!id) {
            console.error("❌ ID introuvable sur la carte");
            return;
        }

        const action = btn.classList.contains("accept") ? "accept" : "decline";

        const confirmed = await swalConfirm(action);
        if (!confirmed.isConfirmed) return;

        console.log("➡️ Envoi :", { id, action });

        try {
            const data = await handleApproval(id, action);
            console.log("✅ Réponse :", data);

            if (data.success) {
                removeCard(card);
                swalSuccess();
            } else {
                swalError(data.message || data.error);
            }
        } catch (err) {
            console.error("❌ Erreur fetch :", err);
            swalError("Server error");
        }
    });
});
