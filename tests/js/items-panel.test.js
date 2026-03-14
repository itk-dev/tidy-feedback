import { describe, it, expect, beforeEach, vi } from "vitest";
import { renderItemsList } from "../../assets/component/items-panel.js";

describe("renderItemsList", () => {
    let ctx;

    beforeEach(() => {
        ctx = {
            form: document.createElement("div"),
            feedbackItems: [],
            config: {
                messages: {
                    "Existing feedback": "Existing feedback",
                },
            },
        };
    });

    it("does nothing when feedbackItems is empty", () => {
        renderItemsList(ctx);

        expect(
            ctx.form.querySelector(".tidy-feedback-items"),
        ).toBeNull();
    });

    it("renders items list with toggle button", () => {
        ctx.feedbackItems = [
            { url: "/fb/1", description: "Bug report" },
            { url: "/fb/2", description: "Feature request" },
        ];

        renderItemsList(ctx);

        const container = ctx.form.querySelector(".tidy-feedback-items");
        expect(container).not.toBeNull();

        const toggle = container.querySelector(
            ".tidy-feedback-items-toggle",
        );
        expect(toggle).not.toBeNull();
        expect(toggle.textContent).toBe("Existing feedback (2)");

        const list = container.querySelector(".tidy-feedback-items-list");
        expect(list.hidden).toBe(true);
        expect(list.querySelectorAll("li").length).toBe(2);
    });

    it("renders items list without toggle when listOnly is true", () => {
        ctx.feedbackItems = [
            { url: "/fb/1", description: "Bug report" },
        ];

        renderItemsList(ctx, true);

        const container = ctx.form.querySelector(".tidy-feedback-items");
        expect(container).not.toBeNull();

        const toggle = container.querySelector(
            ".tidy-feedback-items-toggle",
        );
        expect(toggle).toBeNull();

        const list = container.querySelector(".tidy-feedback-items-list");
        expect(list.hidden).toBe(false);
    });

    it("creates links with correct attributes", () => {
        ctx.feedbackItems = [
            { url: "/fb/1", description: "Bug report" },
        ];

        renderItemsList(ctx, true);

        const link = ctx.form.querySelector("a");
        expect(link.href).toContain("/fb/1");
        expect(link.target).toBe("_blank");
        expect(link.rel).toBe("noopener noreferrer");
        expect(link.textContent).toBe("Bug report");
    });

    it("removes existing items container before re-rendering", () => {
        ctx.feedbackItems = [{ url: "/fb/1", description: "First" }];
        renderItemsList(ctx, true);

        ctx.feedbackItems = [
            { url: "/fb/1", description: "First" },
            { url: "/fb/2", description: "Second" },
        ];
        renderItemsList(ctx, true);

        const containers = ctx.form.querySelectorAll(
            ".tidy-feedback-items",
        );
        expect(containers.length).toBe(1);

        const items = ctx.form.querySelectorAll("li");
        expect(items.length).toBe(2);
    });

    it("toggle button shows/hides the list on click", () => {
        ctx.feedbackItems = [
            { url: "/fb/1", description: "Bug report" },
        ];

        renderItemsList(ctx);

        const toggle = ctx.form.querySelector(
            ".tidy-feedback-items-toggle",
        );
        const list = ctx.form.querySelector(".tidy-feedback-items-list");

        expect(list.hidden).toBe(true);
        toggle.click();
        expect(list.hidden).toBe(false);
        expect(toggle.classList.contains("open")).toBe(true);
        toggle.click();
        expect(list.hidden).toBe(true);
        expect(toggle.classList.contains("open")).toBe(false);
    });
});
