<template>
    <v-container>
      <v-card>
        <v-card-title>Productos Más Vendidos</v-card-title>
        <v-card-text>
          <div class="container">
            <!-- Aquí cargamos la gráfica -->
            <canvas id="graficaProductos" width="400" height="400"></canvas>
          </div>
        </v-card-text>
      </v-card>
    </v-container>
  </template>

  <script>
  import axios from 'axios';
  import Chart from 'chart.js/auto'; // Importa solo lo necesario de Chart.js

  export default {
    created() {
      axios.get('api/v1/productos/masvendidos')
        .then(response => {
          const datosProductos = response.data.ProductoMasVendido;

          // Extraer los nombres de los productos y los totales pagados
          const nombresProductos = datosProductos.map(producto => producto.nombre_producto);
          const totalesPagados = datosProductos.map(producto => producto.TotalPagado);

          // Crear la gráfica de barras
          const ctx = document.getElementById('graficaProductos').getContext('2d');
          const graficaProductos = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: nombresProductos,
              datasets: [{
                label: 'Total Pagado',
                data: totalesPagados,
                backgroundColor: 'rgb(192, 57, 43)', // Color de las barras
                borderColor: 'rgb(252, 7, 7)', // Color del borde de las barras
                borderWidth: 1
              }]
            },
            options: {
              scales: {
                y: {
                  beginAtZero: true // Comenzar el eje y desde cero
                }
              }
            }
          });
        })
        .catch(error => {
          console.error('Error al obtener los datos de la API:', error);
        });
    }
  };
  </script>

  <style>
  /* Agrega estilos necesarios si es necesario */
  </style>
