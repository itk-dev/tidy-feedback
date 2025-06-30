// NOTE! THIS FILE IS A MESS AND NEEDS A CLEAN-UP!

import "./styles/widget.scss";

import { snapdom } from "@zumer/snapdom";
import unique from "@cypress/unique-selector";

const root = document.querySelector("#tidy-feedback").shadowRoot;
const widget = root;

const getHostElement = (selector) => root.querySelector(selector);

const getElement = (selector) => widget.querySelector(selector);
const getActionElement = (action) =>
    getElement(`[data-tidy-feedback-action="${action}"]`);

const config = (() => {
    const el = root.querySelector("[data-tidy-feedback-config]");
    if (el) {
        try {
            return JSON.parse(el.dataset.tidyFeedbackConfig);
        } catch (error) {
            // Ignore all errors.
        }
    }
    return {};
})();

const showMessage = (message) => {
    const el = root.querySelector(".tidy-feedback-message");
    if (el) {
        el.innerHTML = config.messages[message] ?? message;
        el.hidden = !message;
    }
};

let start;
let cancel;
let region;
let firstPoint;
let lastPoint;
let firstElement;
let lastElement;
let form;

const selectRegion = () => {
    root.body.classList.add("tidy-feedback-select-region-drag");

    if (!region) {
        region = root.createElement("div");
        region.classList.add("tidy-feedback-region");
        region.dataset.capture = "exclude"[("nw", "ne", "sw", "se")].forEach(
            (s) => {
                const handle = root.createElement("div");
                handle.classList.add("handle", "handle-" + s);
                region.appendChild(handle);
            },
        );

        root.body.appendChild(region);
    }

    region.hidden = false;
    region.style.left = `10px`;
    region.style.top = `10px`;
    region.style.width = `10px`;
    region.style.height = `10px`;

    const showRegion = (p0, p1) => {
        p0 = p0 || firstPoint;
        p1 = p1 || lastPoint;

        if (p0 && p1) {
            const left = window.scrollX + Math.min(p1.clientX, p0.clientX);
            const top = window.scrollY + Math.min(p1.clientY, p0.clientY);
            const width = Math.abs(p1.clientX - p0.clientX);
            const height = Math.abs(p1.clientY - p0.clientY);

            region.hidden = false;
            region.style.left = left + "px";
            region.style.top = top + "px";
            region.style.width = width + "px";
            region.style.height = height + "px";
        }
    };

    const drawRegion = (event) => {
        event.preventDefault();

        lastPoint = event;
        showRegion();
    };

    const mousedownHandler = (event) => {
        root.body.classList.add("tidy-feedback-select-region");
        root.body.classList.add("tidy-feedback-select-region-dragging");

        showMessage("Extend your selection …");

        firstPoint = event;
        addEventListener("mousemove", mousemoveHandler);
        addEventListener("mouseup", mouseupHandler);

        region.style.left = event.clientX + "px";
        region.style.top = event.clientY + "px";

        drawRegion(event);
    };

    const mousemoveHandler = (event) => {
        drawRegion(event);
    };

    const mouseupHandler = async (event) => {
        // root.body.classList.remove('tidy-feedback-select-region')
        root.body.classList.remove("tidy-feedback-select-region-dragging");

        removeEventListener("mousemove", mousemoveHandler);
        removeEventListener("mouseup", mouseupHandler);
        removeEventListener("mousedown", mousedownHandler);

        // If region is too small, we use the element under the first point as the region.
        //
        // Definition of "too small":
        // * first and last point within same element
        // * first and last point too close

        firstElement = firstPoint
            ? root.elementFromPoint(firstPoint.clientX, firstPoint.clientY)
            : null;
        lastElement = lastPoint
            ? root.elementFromPoint(lastPoint.clientX, lastPoint.clientY)
            : null;

        // Check if "Cancel" has been clicked
        if (firstElement && lastElement && lastElement === cancel) {
            return;
        }

        if (firstElement && (!lastElement || firstElement === lastElement)) {
            // Show first element as region
            const rect = firstElement.getBoundingClientRect();

            showRegion(
                {
                    clientX: rect.left,
                    clientY: rect.top,
                },
                {
                    clientX: rect.right,
                    clientY: rect.bottom,
                },
            );
        }

        showForm();
    };

    addEventListener("mousedown", mousedownHandler);
};

const showForm = async () => {
    form.hidden = false;

    const data = {};

    try {
        const el = root.body;
        const result = await snapdom(el, { scale: 1 });

        data.raw = result.toRaw();

        // const img = await result.toPng();
        // root.body.appendChild(img);

        // const blob = await result.toBlob(el);
        // blob.text().then((svg) => data.svg = svg)
    } catch (error) {
        showMessage("Error taking screen shot");
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        console.log(`POSTing to ${form.action} …`);

        const formData = new FormData(form);
        // https://stackoverflow.com/a/46774073
        formData.forEach((value, key) => (data[key] = value));

        data.context = {
            url: document.location.href,
            referrer: document.referrer,
            document: document.documentElement.outerHTML,
            first_element: firstElement?.outerHTML,
            first_element_selector: firstElement ? unique(firstElement) : null,
            last_element: firstElement?.outerHTML,
            last_element_selector: firstElement ? unique(firstElement) : null,
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

        console.log(data);
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
                    form.hidden = true;
                    form.reset();
                    showMessage("Feedback created");
                } else {
                    response
                        .json()
                        .then((data) =>
                            showMessage(
                                "Error creating feedback: " +
                                    JSON.stringify(data),
                            ),
                        );
                }
            })
            .catch((reason) => alert(reason));
    });
};

addEventListener("load", () => {
    form = getElement(".tidy-feedback-form");
    start = getActionElement("start");
    cancel = getActionElement("cancel");
    region = getElement(".tidy-feedback-region");

    if (region) {
        const s = getElement("[data-tidy-feedback-action='select-region']");
        if (s) {
            s.addEventListener("click", selectRegion);
        }
    }

    if (start) {
        start.hidden = false;
        start.addEventListener("click", (event) => {
            start.hidden = true;
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
            if (form) {
                form.reset();
                form.hidden = true;
            }
            if (start) {
                start.hidden = false;
            }
        });
    }
});
