<!-- Footer -->
<footer style="background-color: #222; color: #fff; padding: 30px 0; margin-top: 40px;">
        <div class="container">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between;">
                <!-- Columna 1: Información de la tienda -->
                <div style="flex: 1; min-width: 250px; margin-bottom: 20px;">
                    <h3 style="margin-bottom: 15px; font-size: 18px;"><?php echo SITE_NAME; ?></h3>
                    <p style="line-height: 1.6; margin-bottom: 10px;">Tu tienda de confianza en tecnología y computadoras. Encuentra los mejores precios en laptops, PC gamer, componentes y más.</p>
                    <p style="line-height: 1.6; margin-bottom: 10px;">Dirección: Av. Ejemplo 123, Lima</p>
                    <p style="line-height: 1.6; margin-bottom: 10px;">Teléfono: (01) 123-4567</p>
                    <p style="line-height: 1.6; margin-bottom: 10px;">Email: info@compuyatienda.com</p>
                </div>
                
                <!-- Columna 2: Enlaces rápidos -->
                <div style="flex: 1; min-width: 250px; margin-bottom: 20px;">
                    <h3 style="margin-bottom: 15px; font-size: 18px;">Enlaces rápidos</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;"><a href="<?php echo BASE_URL; ?>" style="color: #fff; text-decoration: none;">Inicio</a></li>
                        <li style="margin-bottom: 10px;"><a href="<?php echo BASE_URL; ?>/public/categoria.php?slug=pc-gamer" style="color: #fff; text-decoration: none;">PC Gamer</a></li>
                        <li style="margin-bottom: 10px;"><a href="<?php echo BASE_URL; ?>/public/categoria.php?slug=laptops" style="color: #fff; text-decoration: none;">Laptops</a></li>
                        <li style="margin-bottom: 10px;"><a href="<?php echo BASE_URL; ?>/public/categoria.php?slug=componentes" style="color: #fff; text-decoration: none;">Componentes</a></li>
                        <li style="margin-bottom: 10px;"><a href="<?php echo BASE_URL; ?>/public/contacto.php" style="color: #fff; text-decoration: none;">Contacto</a></li>
                    </ul>
                </div>
                
                <!-- Columna 3: Horario -->
                <div style="flex: 1; min-width: 250px; margin-bottom: 20px;">
                    <h3 style="margin-bottom: 15px; font-size: 18px;">Horario de atención</h3>
                    <p style="line-height: 1.6; margin-bottom: 10px;">Lunes a Viernes: 9:00 am - 8:00 pm</p>
                    <p style="line-height: 1.6; margin-bottom: 10px;">Sábados: 9:00 am - 6:00 pm</p>
                    <p style="line-height: 1.6; margin-bottom: 10px;">Domingos: 10:00 am - 4:00 pm</p>
                </div>
            </div>
            
            <!-- Derechos de autor -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #444; text-align: center;">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Script básico para carruseles
        document.addEventListener('DOMContentLoaded', function() {
            // Función para manejar carruseles
            // Se implementará según sea necesario
        });
    </script>
</body>
</html>