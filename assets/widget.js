// NOTE! THIS FILE IS A MESS AND NEEDS A CLEAN-UP!

import "./styles/widget.scss";
import { makeResizableDiv } from "./component/region";
import { makeDraggable } from "./component/draggable.js";

import { snapdom } from "@zumer/snapdom";
import unique from "@cypress/unique-selector";

const root = document.querySelector("#tidy-feedback").shadowRoot;
const widget = root ?? document;

const getElement = (selector) => widget.querySelector(selector);
const getActionElement = (action) =>
    getElement(`[data-tidy-feedback-action="${action}"]`);

const getDocumentElement = (selector) => document.querySelector(selector);

const config = (() => {
    const el = getElement("[data-tidy-feedback-config]");
    if (el) {
        try {
            return JSON.parse(el.dataset.tidyFeedbackConfig);
        } catch (error) {
            // Ignore all errors.
        }
    }
    return {};
})();

const data = {};

const showMessage = (message, type = "default") => {
    const el = root.querySelector(".tidy-feedback-message");
    if (el) {
        el.classList.remove("success", "warning", "danger", "default");

        if (type) {
            el.classList.add(type);
        }

        el.innerHTML = config.messages[message] ?? message;
        el.hidden = !message;
    }
};

let start;
let cancel;
let region;
let firstElement;
let form;
let dragCleanup;

const selectRegion = () => {
    if (region) {
        region.parentNode.hidden = false;
        // @todo move region into view.
        region.style.left = "300px";
        region.style.top = "300px";
        region.style.width = "300px";
        region.style.height = "200px";
        makeResizableDiv(region);
    }
};

const hideRegion = () => {
    if (region) {
        region.parentNode.hidden = true;
    }
};

const makeFormDraggable = () => {
    if (dragCleanup) {
        return;
    }

    const tidyFeedbackDiv = getElement(".tidy-feedback-form");
    const dragHandle = getElement(".tidy-feedback-draggable-handle");

    if (tidyFeedbackDiv && dragHandle) {
        dragHandle.hidden = false;

        dragCleanup = makeDraggable(tidyFeedbackDiv, dragHandle, {
            constrainToViewport: true,
            onDragStart: (e, element) => {
                console.log("Started dragging feedback form");
            },
            onDragEnd: (e, element) => {
                console.log("Finished dragging feedback form");
            },
        });
    }
};

const hideFormDragHandle = () => {
    const tidyFeedbackDiv = getDocumentElement("#tidy-feedback");
    const dragHandle = tidyFeedbackDiv?.querySelector(
        ".tidy-feedback-draggable-handle",
    );

    if (dragHandle) {
        dragHandle.hidden = true;
    }
};

const showForm = async () => {
    if (form) {
        form.hidden = false;
        selectRegion();
    }
    if (start) {
        start.hidden = true;
    }

    makeFormDraggable();

    //
    // try {
    //     const el = root.body;
    //     const result = await snapdom(el, { scale: 1 });
    //
    //     data.raw = result.toRaw();
    //
    //     // const img = await result.toPng();
    //     // root.body.appendChild(img);
    //
    //     // const blob = await result.toBlob(el);
    //     // blob.text().then((svg) => data.svg = svg)
    // } catch (error) {
    //     showMessage("Error taking screen shot");
    // }
};

const hideForm = (reset) => {
    hideRegion();
    hideFormDragHandle();
    if (form) {
        form.hidden = true;
        if (reset) {
            form.reset();
        }
    }
    if (start) {
        start.hidden = false;
    }

    // Hide the drag handle when form is hidden
    const tidyFeedbackDiv = document.getElementById("tidy-feedback");
    const dragHandle = tidyFeedbackDiv?.querySelector(
        ".tidy-feedback-draggable-handle",
    );
    if (dragHandle) {
        dragHandle.hidden = true;
    }
};

addEventListener("load", () => {
    form = getElement(".tidy-feedback-form");
    start = getActionElement("start");
    cancel = getActionElement("cancel");
    region = getDocumentElement("#tidy-feedback-region > .resizable");

    if (form) {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            try {
                const el = document.body;
                const result = await snapdom(el, { scale: 1 });

                data.raw = result.toRaw();
            } catch (error) {
                showMessage("Error taking screen shot", "danger");
            }

            console.log(`POSTing to ${form.action} …`);

            const formData = new FormData(form);
            // https://stackoverflow.com/a/46774073
            formData.forEach((value, key) => (data[key] = value));

            data.context = {
                url: document.location.href,
                referrer: document.referrer,
                document: document.documentElement.outerHTML,
                first_element: firstElement?.outerHTML,
                first_element_selector: firstElement
                    ? unique(firstElement)
                    : null,
                last_element: firstElement?.outerHTML,
                last_element_selector: firstElement
                    ? unique(firstElement)
                    : null,
                navigator: {
                    userAgent: navigator.userAgent,
                },
                window: {
                    innerWidth: window.innerWidth,
                    innerHeight: window.innerHeight,
                },
            };

            // const response = fetch(form.action, {
            //   method: 'POST',
            //   body: formData
            // });

            console.log({ data });
            fetch(form.action, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(data),
            })
                .then((response) => {
                    console.log({ response });
                    if (201 === response.status) {
                        if (region) {
                            region.hidden = true;
                        }
                        hideForm(true);
                        showMessage("Feedback created", "success");
                    } else {
                        response
                            .json()
                            .then((data) =>
                                showMessage(
                                    "Error creating feedback: " +
                                        JSON.stringify(data),
                                    "danger",
                                ),
                            );
                    }
                })
                .catch((reason) => alert(reason));
        });
    }

    const s = getElement("[data-tidy-feedback-action='select-region']");
    if (s) {
        s.addEventListener("click", selectRegion);
    }

    if (start) {
        start.hidden = false;
        start.addEventListener("click", (event) => {
            showForm();

            // if (cancel) {
            //     cancel.hidden = false;
            // }
            // showMessage(
            //     "Click on an element or click and drag to mark a region …",
            // );
            // selectRegion();
        });
    }

    if (cancel) {
        cancel.addEventListener("click", (event) => {
            showMessage("");
            // TODO Confirm if form not empty.
            hideForm(true);
        });
    }

    const params = new URLSearchParams(document.location.search);
    switch (params.get("tidy-feedback-show")) {
        case "form":
            showForm();
            break;
    }
});
