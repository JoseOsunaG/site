<?php include("conexion.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema POS | Catálogo de Etiquetas</title>
    <script src="https://cdn.jsdelivr.net/npm/quagga/dist/quagga.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        :root { --primary: #2563eb; --dark: #1e293b; --success: #22c55e; --accent: #f59e0b; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #f1f5f9; margin: 0; }
        header { background: var(--dark); color: white; padding: 1rem; text-align: center; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        
        
        .mode-selector { display: flex; gap: 10px; margin-bottom: 25px; }
        .mode-btn { flex: 1; padding: 12px; border: 2px solid var(--primary); background: transparent; color: var(--primary); cursor: pointer; border-radius: 8px; font-weight: bold; transition: 0.3s; }
        .mode-btn.active { background: var(--primary); color: white; }

        input { width: 100%; padding: 12px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
        .btn-main { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        
       
        .grid-catalogo { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; }
        .etiqueta-card { border: 1px solid #eee; padding: 15px; text-align: center; border-radius: 10px; background: #fff; }
        .etiqueta-card svg { max-width: 100%; height: auto; margin: 10px 0; }
        
        
        .total-box { font-size: 1.5rem; text-align: right; padding: 15px; background: #f1f5f9; border-radius: 8px; margin-top: 10px; border: 2px solid var(--primary); }

        @media print { 
            .no-print { display: none !important; } 
            .print-only { display: block !important; position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body>

<header class="no-print"><h1> Gestión de Inventario y Etiquetas</h1></header>

<div class="container">
    <div class="mode-selector no-print">
        <button class="mode-btn active" id="btn-reg" onclick="setModo('registro')"> Nuevo Producto</button>
        <button class="mode-btn" id="btn-cat" onclick="setModo('catalogo')"> Catálogo de Etiquetas</button>
        <button class="mode-btn" id="btn-ven" onclick="setModo('venta')"> Modo Tienda</button>
    </div>

    <div id="sec-registro" class="card no-print">
        <h3>Registrar y Generar</h3>
        <form action="insertar.php" method="POST">
            <input type="text" name="nombre" id="n_prod" placeholder="Nombre del Producto" required onkeyup="updatePreview()">
            <input type="number" step="0.01" name="precio" placeholder="Precio" required>
            <input type="text" name="codigo_barras" id="c_auto" readonly placeholder="Código automático">
            <div id="preview-box" style="text-align:center; padding:10px; display:none;">
                <svg id="preview-svg"></svg>
            </div>
            <button type="submit" class="btn-main">Guardar Producto</button>
        </form>
    </div>

    <div id="sec-catalogo" style="display:none;">
        <div class="grid-catalogo no-print">
            <?php
            $res = mysqli_query($conexion, "SELECT * FROM productos ORDER BY nombre ASC");
            while($f = mysqli_fetch_assoc($res)) {
                $uid = "bc_" . $f['id'];
                echo "<div class='etiqueta-card'>
                        <strong>{$f['nombre']}</strong><br>
                        <small>\${$f['precio']}</small><br>
                        <svg id='$uid' class='barcode-item' data-code='{$f['codigo_barras']}'></svg>
                        <button class='btn-main' style='font-size:10px; padding:5px;' onclick='imprimirUna(\"$uid\")'>Imprimir</button>
                      </div>";
            }
            ?>
        </div>
    </div>

    <div id="sec-venta" style="display:none;" class="card">
        <h3>Caja de Venta</h3>
        <div class="no-print">
            <div id="interactive" style="width:100%; height:150px; background:#000; border-radius:8px; overflow:hidden;"></div>
            <input type="text" id="scan_input" placeholder="Escanee aquí..." autofocus style="margin-top:15px;">
        </div>
        <table>
            <thead><tr><th>Producto</th><th>Precio</th></tr></thead>
            <tbody id="cart-body"></tbody>
        </table>
        <div class="total-box">TOTAL: <span id="total-val">$0.00</span></div>
        <button class="btn-main no-print" style="background:var(--success); margin-top:10px;" onclick="window.print()">Finalizar Venta</button>
    </div>
</div>

<script>
    function setModo(m) {
        
        document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('btn-' + m.substring(0,3)).classList.add('active');
        
       
        document.getElementById('sec-registro').style.display = (m === 'registro') ? 'block' : 'none';
        document.getElementById('sec-catalogo').style.display = (m === 'catalogo') ? 'block' : 'none';
        document.getElementById('sec-venta').style.display = (m === 'venta') ? 'block' : 'none';

        if(m === 'catalogo') renderCatalogo();
    }

    
    function updatePreview() {
        const nom = document.getElementById('n_prod').value;
        if(nom.length > 2) {
            const code = "T" + Date.now().toString().slice(-8);
            document.getElementById('c_auto').value = code;
            document.getElementById('preview-box').style.display = 'block';
            JsBarcode("#preview-svg", code, { height: 40, fontSize: 12 });
        }
    }

    
    function renderCatalogo() {
        document.querySelectorAll('.barcode-item').forEach(svg => {
            JsBarcode("#" + svg.id, svg.getAttribute('data-code'), { height: 40, fontSize: 12 });
        });
    }

    
    let carrito = [];
    document.getElementById('scan_input').addEventListener('keypress', (e) => {
        if(e.key === 'Enter') {
            fetch('buscar_producto.php?codigo=' + e.target.value)
            .then(r => r.json()).then(data => {
                if(data.success) {
                    carrito.push(data);
                    let h = ''; let t = 0;
                    carrito.forEach(i => { h += `<tr><td>${i.nombre}</td><td>$${i.precio}</td></tr>`; t += parseFloat(i.precio); });
                    document.getElementById('cart-body').innerHTML = h;
                    document.getElementById('total-val').innerText = '$' + t.toFixed(2);
                }
            });
            e.target.value = '';
        }
    });

    function imprimirUna(id) {
        const svg = document.getElementById(id).outerHTML;
        const win = window.open('', '_blank');
        win.document.write('<html><body><div style="text-align:center;">' + svg + '</div></body></html>');
        win.document.close();
        win.print();
    }
</script>
</body>
</html>
