<script type="text/javascript">
    var myEditor;
</script>

<button onclick="console.log(myEditor.getValue())">Dump to console</button>
<div id="htmlEditor" style="width:800px;height:600px;border:1px solid grey"></div>

<script>
    {literal}
    require.config({
        paths: CRM.vars.afform_html.paths
    });
    require(['vs/editor/editor.main'], function () {
        myEditor = monaco.editor.create(document.getElementById('htmlEditor'), {
            value: [
                '<div afform-helloworld>\nHello {{routeParams.name}}\n</div>'
            ].join('\n'),
            language: 'html',
            theme: 'vs-dark',
            tabSize: 2,
            minimap: {
                enabled: false
            }
        });
    });
    {/literal}
</script>
