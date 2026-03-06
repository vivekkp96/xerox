export const removeQueryParam = (param: string, delay: number = 0) => {
    const remove = () => {
        const url = new URL(window.location.href);
        if (url.searchParams.has(param)) {
            url.searchParams.delete(param);
            window.history.replaceState(window.history.state, '', url.toString());
        }
    };

    if (delay > 0) {
        setTimeout(remove, delay);
    } else {
        remove();
    }
};