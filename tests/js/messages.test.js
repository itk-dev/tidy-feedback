import { describe, it, expect, beforeEach } from "vitest";
import { showMessage } from "../../assets/component/messages.js";

describe("showMessage", () => {
    let root;
    let messageEl;
    const config = {
        messages: {
            "Feedback created": "<strong>Thanks!</strong> Feedback saved.",
        },
    };

    beforeEach(() => {
        root = document.createElement("div");
        messageEl = document.createElement("div");
        messageEl.className = "tidy-feedback-message";
        messageEl.hidden = true;
        root.appendChild(messageEl);
    });

    it("shows a message with the given type", () => {
        showMessage(root, config, "Hello", "success");

        expect(messageEl.hidden).toBe(false);
        expect(messageEl.innerHTML).toBe("Hello");
        expect(messageEl.classList.contains("success")).toBe(true);
    });

    it("hides the element when message is empty", () => {
        showMessage(root, config, "Hello", "success");
        showMessage(root, config, "");

        expect(messageEl.hidden).toBe(true);
    });

    it("uses translated message from config when available", () => {
        showMessage(root, config, "Feedback created", "success");

        expect(messageEl.innerHTML).toBe(
            "<strong>Thanks!</strong> Feedback saved.",
        );
    });

    it("removes previous type classes before adding new one", () => {
        showMessage(root, config, "First", "success");
        showMessage(root, config, "Second", "danger");

        expect(messageEl.classList.contains("success")).toBe(false);
        expect(messageEl.classList.contains("danger")).toBe(true);
    });

    it("removes all type classes when no type is given", () => {
        showMessage(root, config, "First", "select");
        showMessage(root, config, "Second");

        expect(messageEl.classList.contains("select")).toBe(false);
        expect(messageEl.classList.contains("success")).toBe(false);
        expect(messageEl.classList.contains("warning")).toBe(false);
        expect(messageEl.classList.contains("danger")).toBe(false);
    });

    it("does nothing when message element is not found", () => {
        const emptyRoot = document.createElement("div");
        // Should not throw.
        showMessage(emptyRoot, config, "Hello", "success");
    });
});
