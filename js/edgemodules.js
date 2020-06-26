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

    static setupHanldebars()
    {
        Handlebars.registerHelper('replace', (inp, re, repl) => inp.replace(new RegExp(re, 'g'), repl));
        Handlebars.registerHelper('ifEq', function (a, b, options) {
            if (a === b) {
                return options.fn(this);
            } else {
                return options.inverse(this);
            }
        });
        Handlebars.registerHelper('ifMatch', (a, pat, opts) => opts[a.match(new RegExp(pat)) ? 'fn':'inverse'](this));
        Handlebars.registerHelper('extract', (a, pat) => (a.match(new RegExp(pat)) || [])[1]);
    }

    static disableModule(name)
    {
        return document.querySelector(`form[id="${name}-disable-form"]`).submit();
    }

    static submit(form)
    {
        this.setupHanldebars();

        // let parsedVcl = JSON.stringify(parseVcl(fieldData));
        var key = form.querySelector(`[id$="key"]`).value;
        var snippet = form.querySelector(`[id$="snippet"]`);
        var vcl = JSON.parse(unescape(form.querySelector(`[id$="vcl"]`).value));

        snippet.value = this.generateSnippet(vcl, this.getSnippetData(form, key));

        return true;
    }

    static getSnippetData(form, key)
    {
        var formData = jQuery(form).serializeJSON()[key]
        delete formData['snippet'];
        delete formData['vcl'];
        return formData;
    }

    static generateSnippet(vcls, data)
    {
        let templates = [];
        for (const vcl of vcls) {
            let vclTemplate = Handlebars.compile(vcl.template);
            templates.push({
                "type": vcl.type,
                "priority": vcl.priority ? vcl.priority : 45,
                "snippet": vclTemplate(data)
            });
        }

        return escape(JSON.stringify(templates));
    }
}
