class EdgeModules
{
    static removeGroup(e)
    {
        e.closest('div').remove();
        return false;
    }

    static addGroup(id, name)
    {
        var template = document.querySelector(`#${id}-template`);
        var target = template.parentElement;

        var clone = template.content.cloneNode(true);
        var count = document.querySelectorAll(`div[id^="${id}"]`).length;
        clone.querySelector('#container').id = `${id}-${count}`;

        var list = clone.querySelectorAll(`[name^="${name}"]`);
        for (let element of list) {
            element.name = element.name.replace('[x]', `[${count}]`);
        }

        target.appendChild(clone);
        return false;
    }
}
