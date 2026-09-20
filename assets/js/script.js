// HaatKatha — small helper for the AI-assist buttons on the product form.
// Only runs on pages that actually have these elements.

document.addEventListener('DOMContentLoaded', function () {
  const status = document.getElementById('aiStatus');

  function setStatus(text) {
    if (status) status.textContent = text;
  }

  async function callAi(payload) {
    setStatus('Asking AI...');
    try {
      const body = new URLSearchParams(payload);
      const res = await fetch('ai_action.php', { method: 'POST', body });
      const data = await res.json();
      setStatus('');
      return data;
    } catch (err) {
      setStatus('');
      alert('Something went wrong talking to the AI service.');
      return null;
    }
  }

  const btnDesc = document.getElementById('btnGenerateDesc');
  if (btnDesc) {
    btnDesc.addEventListener('click', async function () {
      const name = document.getElementById('p_name').value;
      const material = document.getElementById('p_material').value;
      if (!name) { alert('Enter a product name first.'); return; }
      const data = await callAi({
        type: 'description',
        name: name,
        material: material,
        location: '',
      });
      if (data && data.ok) {
        document.getElementById('p_description').value = data.text;
      } else if (data) {
        alert(data.text);
      }
    });
  }

  function wireTranslateButton(buttonId, targetLang) {
    const btn = document.getElementById(buttonId);
    if (!btn) return;
    btn.addEventListener('click', async function () {
      const desc = document.getElementById('p_description');
      if (!desc.value) { alert('Write or generate a description first.'); return; }
      const data = await callAi({ type: 'translate', text: desc.value, target: targetLang });
      if (data && data.ok) {
        desc.value = data.text;
      } else if (data) {
        alert(data.text);
      }
    });
  }
  wireTranslateButton('btnTranslateDesc', 'as');
  wireTranslateButton('btnTranslateDescEn', 'en');

  const btnKeywords = document.getElementById('btnSuggestKeywords');
  if (btnKeywords) {
    btnKeywords.addEventListener('click', async function () {
      const name = document.getElementById('p_name').value;
      const material = document.getElementById('p_material').value;
      const categorySelect = document.getElementById('p_category');
      const category = categorySelect ? categorySelect.options[categorySelect.selectedIndex].text : '';
      if (!name) { alert('Enter a product name first.'); return; }
      const data = await callAi({ type: 'keywords', name: name, material: material, category: category });
      if (data && data.ok) {
        document.getElementById('p_keywords').value = data.text;
      } else if (data) {
        alert(data.text);
      }
    });
  }
});
