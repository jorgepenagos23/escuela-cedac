<template>
    <div class="container mx-auto p-4">
      <div class="flex flex-wrap -mx-2">
        <div class="w-full md:w-1/2 px-2 mb-4">
          <div class="bg-indigo-500 text-white p-4 rounded-lg shadow-lg">
            <h2 class="text-lg font-bold mb-2">Selecciona una fecha de Inicio</h2>
            <v-date-picker v-model="fechaInicio" class="w-full" />
          </div>
        </div>
        <div class="w-full md:w-1/2 px-2 mb-4">
          <div class="bg-yellow-500 text-white p-4 rounded-lg shadow-lg">
            <h2 class="text-lg font-bold mb-2">Selecciona una fecha de Fin</h2>
            <v-date-picker v-model="fechaFin" class="w-full" />
          </div>
          <button
            @click="enviarFecha"
            class="bg-red-500 text-white mt-4 px-4 py-2 rounded-lg shadow-lg w-full"
          >
            <span class="mdi mdi-database-search"></span> Obtener
          </button>
        </div>
      </div>
      <div v-if="SumaPagos.length > 0" class="mt-8">
        <div v-for="(producto, index) in SumaPagos" :key="index" class="mb-4">
          <div class="bg-lime-200 p-4 rounded-lg shadow-lg">
            <h3 class="text-xl font-bold mb-2">{{ producto.Diplomado }}</h3>
            <div class="text-green-800 text-lg font-semibold">
              Colegiaturas : {{ formatNumber(producto.TotalPagadoAbono) }} MXN
            </div>
          </div>
        </div>
      </div>
      <div v-else class="mt-8">
        <div class="bg-gray-200 p-4 rounded-lg shadow-lg">
          <h3 class="text-xl font-bold mb-2">No hay resultados</h3>
          <p class="text-gray-800">No se encontraron resultados para el período de fechas seleccionado.</p>
        </div>
      </div>
    </div>
  </template>

  <script>
  import swal from 'sweetalert';

  export default {

      data(){
          return{
              fechaInicio:null,
              fechaFin:null,
              SumaPagos:[]

          }

      },

      created(){

      },


      components:{

      },

      methods:{

          formatNumber(number) {
              // Utiliza toLocaleString para formatear el número con comas
              return number.toLocaleString();
          },

          enviarFecha(){
              let  fechaInicio = this.fechaInicio;
              let   fechaFin =this.fechaFin;
              console.log('Fecha Inicio',fechaInicio)
              console.log('Fecha Final',fechaFin)

              axios.post('/api/v1/diplomados/fechaconsulta', {
                  fechaInicio: this.fechaInicio,
                  fechaFin: this.fechaFin
              })
              .then(response => {
                  this.SumaPagos = response.data.SumaPagos;
                  console.log('Envio exitoso', response);
              })
              .catch(function (error) {
                  console.log('Error',error);
              })
          },

          recibirDatos(){
          }
      }
  }
  </script>

  <style scoped>
  /* Estilos para dispositivos móviles */
  @media (max-width: 640px) {
    .w-full {
      width: 100% !important; /* Hacer que los elementos ocupen el ancho completo */
    }
  }
  </style>
