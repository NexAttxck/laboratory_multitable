// js/app.js
// Small progressive enhancements. The app works without this file.
(function ()
{
    'use strict';

    // ----- 1. Scroll-aware navigation (stronger glass after scrolling) -----
    var nav = document.querySelector('nav');
    var ticking = false;

    function UpdateNav()
    {
        if (nav)
        {
            nav.classList.toggle('is-scrolled', window.scrollY > 8);
        }
        ticking = false;
    }

    window.addEventListener('scroll', function ()
    {
        if (!ticking)
        {
            ticking = true;
            window.requestAnimationFrame(UpdateNav);
        }
    }, { passive: true });
    UpdateNav();

    // ----- 2. Glass confirm dialog (replaces the native confirm box) -----
    // Forms with data-confirm get this dialog. The old inline onsubmit is
    // removed only when JS runs, so the native confirm still works without JS.
    var confirmForms = document.querySelectorAll('form[data-confirm]');
    for (var i = 0; i < confirmForms.length; i++)
    {
        confirmForms[i].removeAttribute('onsubmit');
    }

    function AskConfirm(form, submitter)
    {
        var isDanger = submitter && submitter.classList.contains('danger');
        var verb = submitter ? submitter.textContent.trim().toLowerCase() : 'continue';

        var dlg = document.createElement('dialog');
        dlg.className = 'confirm';
        dlg.setAttribute('aria-labelledby', 'confirm-title');
        dlg.setAttribute('aria-describedby', 'confirm-text');

        var title = document.createElement('h2');
        title.id = 'confirm-title';
        title.textContent = 'Please confirm';

        var text = document.createElement('p');
        text.id = 'confirm-text';
        text.textContent = form.getAttribute('data-confirm');

        var actions = document.createElement('div');
        actions.className = 'confirm-actions';

        var back = document.createElement('button');
        back.type = 'button';
        back.className = 'secondary';
        back.textContent = 'Go back';
        back.autofocus = true; // safe default focus

        var ok = document.createElement('button');
        ok.type = 'button';
        ok.className = isDanger ? 'danger solid' : '';
        ok.textContent = 'Yes, ' + verb;

        actions.appendChild(back);
        actions.appendChild(ok);
        dlg.appendChild(title);
        dlg.appendChild(text);
        dlg.appendChild(actions);
        document.body.appendChild(dlg);

        back.addEventListener('click', function () { dlg.close(); });

        ok.addEventListener('click', function ()
        {
            form.setAttribute('data-confirmed', '1');
            dlg.close();
            if (form.requestSubmit)
            {
                form.requestSubmit(submitter || undefined);
            }
            else
            {
                form.submit();
            }
        });

        // Click on the dimmed area closes the dialog
        dlg.addEventListener('click', function (e)
        {
            if (e.target === dlg) { dlg.close(); }
        });

        // Escape is handled natively by <dialog>. Clean up when closed.
        dlg.addEventListener('close', function () { dlg.remove(); });

        if (typeof dlg.showModal === 'function')
        {
            dlg.showModal();
        }
        else
        {
            // Very old browsers: fall back to the native confirm
            dlg.remove();
            if (window.confirm(form.getAttribute('data-confirm')))
            {
                form.setAttribute('data-confirmed', '1');
                form.submit();
            }
        }
    }

    // ----- 3. Submit handling: confirm dialog + double-submit protection -----
    document.addEventListener('submit', function (e)
    {
        var form = e.target;

        if (form.hasAttribute('data-confirm') && form.getAttribute('data-confirmed') !== '1')
        {
            e.preventDefault();
            AskConfirm(form, e.submitter);
            return;
        }

        // A second click while the first request is running is ignored.
        // This protects enroll and cancel from being sent twice.
        if (form.getAttribute('data-busy') === '1')
        {
            e.preventDefault();
            return;
        }
        form.setAttribute('data-busy', '1');

        // Delay so the submitter's name and value are still sent
        var btn = e.submitter;
        window.setTimeout(function ()
        {
            if (btn) { btn.setAttribute('aria-busy', 'true'); }
        }, 0);
    });

    // Restore forms when the user comes back with the browser Back button
    window.addEventListener('pageshow', function (e)
    {
        if (!e.persisted) { return; }
        var forms = document.querySelectorAll('form[data-busy], form[data-confirmed]');
        for (var j = 0; j < forms.length; j++)
        {
            forms[j].removeAttribute('data-busy');
            forms[j].removeAttribute('data-confirmed');
        }
        var busy = document.querySelectorAll('[aria-busy]');
        for (var k = 0; k < busy.length; k++)
        {
            busy[k].removeAttribute('aria-busy');
        }
    });
})();
