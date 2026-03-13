import { describe, it, expect, beforeEach, vi } from "vitest";
import { initKeyboardShortcuts } from "../../assets/component/keyboard.js";

describe("initKeyboardShortcuts", () => {
    let ctx;

    beforeEach(() => {
        ctx = {
            start: document.createElement("div"),
            form: document.createElement("form"),
            itemsPanelMode: false,
            showForm: vi.fn(),
            showMessage: vi.fn(),
            hideForm: vi.fn(),
            hideItemsPanel: vi.fn(),
        };
        ctx.start.hidden = false;
        ctx.form.hidden = false;
    });

    it("calls showForm on Shift+C when start button is visible", () => {
        initKeyboardShortcuts(ctx);

        document.dispatchEvent(
            new KeyboardEvent("keydown", {
                key: "C",
                shiftKey: true,
                bubbles: true,
            }),
        );

        expect(ctx.showForm).toHaveBeenCalledOnce();
    });

    it("does not call showForm on Shift+C when start button is hidden", () => {
        ctx.start.hidden = true;
        initKeyboardShortcuts(ctx);

        document.dispatchEvent(
            new KeyboardEvent("keydown", {
                key: "C",
                shiftKey: true,
                bubbles: true,
            }),
        );

        expect(ctx.showForm).not.toHaveBeenCalled();
    });

    it("does not call showForm on Shift+C when typing in an input", () => {
        initKeyboardShortcuts(ctx);

        const input = document.createElement("input");
        document.body.appendChild(input);
        input.focus();

        input.dispatchEvent(
            new KeyboardEvent("keydown", {
                key: "C",
                shiftKey: true,
                bubbles: true,
            }),
        );

        expect(ctx.showForm).not.toHaveBeenCalled();
        input.remove();
    });

    it("submits form on Ctrl+Enter when form is visible", () => {
        ctx.form.requestSubmit = vi.fn();
        initKeyboardShortcuts(ctx);

        document.dispatchEvent(
            new KeyboardEvent("keydown", {
                key: "Enter",
                ctrlKey: true,
                bubbles: true,
            }),
        );

        expect(ctx.form.requestSubmit).toHaveBeenCalledOnce();
    });

    it("does not submit form on Ctrl+Enter when in items panel mode", () => {
        ctx.form.requestSubmit = vi.fn();
        ctx.itemsPanelMode = true;
        initKeyboardShortcuts(ctx);

        document.dispatchEvent(
            new KeyboardEvent("keydown", {
                key: "Enter",
                ctrlKey: true,
                bubbles: true,
            }),
        );

        expect(ctx.form.requestSubmit).not.toHaveBeenCalled();
    });

    it("hides items panel on Escape when in items panel mode", () => {
        ctx.itemsPanelMode = true;
        initKeyboardShortcuts(ctx);

        document.dispatchEvent(
            new KeyboardEvent("keydown", {
                key: "Escape",
                bubbles: true,
            }),
        );

        expect(ctx.hideItemsPanel).toHaveBeenCalledOnce();
    });

    it("hides form on Escape when form is visible", () => {
        initKeyboardShortcuts(ctx);

        document.dispatchEvent(
            new KeyboardEvent("keydown", {
                key: "Escape",
                bubbles: true,
            }),
        );

        expect(ctx.showMessage).toHaveBeenCalledWith("");
        expect(ctx.hideForm).toHaveBeenCalledWith(true);
    });

    it("does nothing on Escape when form is hidden", () => {
        ctx.form.hidden = true;
        initKeyboardShortcuts(ctx);

        document.dispatchEvent(
            new KeyboardEvent("keydown", {
                key: "Escape",
                bubbles: true,
            }),
        );

        expect(ctx.hideForm).not.toHaveBeenCalled();
        expect(ctx.hideItemsPanel).not.toHaveBeenCalled();
    });
});
