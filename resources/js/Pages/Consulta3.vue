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
      <div  v-if="SumaPagos.length >0" class="grid grid-cols-1 gap-2 sm:grid-cols-1 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-3 mt-3">
        <div v-for="(producto, index)  in SumaPagos" :key="index" class="relative w-full h-52 bg-cover bg-center group rounded-lg overflow-hidden shadow-lg transition duration-300 ease-in-out"
          style="background-image: url('https://images-wixmp-ed30a86b8c4ca887773594c2.wixmp.com/f/f868ecef-4b4a-4ddf-8239-83b2568b3a6b/de7hhu3-3eae646a-9b2e-4e42-84a4-532bff43f397.jpg?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ1cm46YXBwOjdlMGQxODg5ODIyNjQzNzNhNWYwZDQxNWVhMGQyNmUwIiwiaXNzIjoidXJuOmFwcDo3ZTBkMTg4OTgyMjY0MzczYTVmMGQ0MTVlYTBkMjZlMCIsIm9iaiI6W1t7InBhdGgiOiJcL2ZcL2Y4NjhlY2VmLTRiNGEtNGRkZi04MjM5LTgzYjI1NjhiM2E2YlwvZGU3aGh1My0zZWFlNjQ2YS05YjJlLTRlNDItODRhNC01MzJiZmY0M2YzOTcuanBnIn1dXSwiYXVkIjpbInVybjpzZXJ2aWNlOmZpbGUuZG93bmxvYWQiXX0.R0h-BS0osJSrsb1iws4-KE43bUXHMFvu5PvNfoaoi8o');">
            <div class="absolute inset-0 bg-green-900 bg-opacity-75 transition duration-300 ease-in-out"></div>
            <div class="relative w-full h-full px-4 sm:px-6 lg:px-4 flex items-center justify-center">
              <div>
                <h3 class="text-center text-white text-lg">
                {{ producto.Diplomado }}

                </h3>
                <h3 class="text-center text-white text-3xl mt-2 font-bold">
                    $  {{ formatNumber(producto.TotalPagadoAbono) }} MXN

                </h3>
                <div class="flex space-x-4 mt-4">
                   <button class="block uppercase mx-auto shadow bg-white text-indigo-600 focus:shadow-outline
                    focus:outline-none text-black text-xs py-3 px-4 rounded font-bold">
                    Colegiaturas
                  </button>
                  <button class="block uppercase mx-auto shadow bg-indigo-800 hover:bg-indigo-700 focus:shadow-outline
                     focus:outline-none text-white text-xs py-3 px-4 rounded font-bold">
                    Total
                  </button>
                </div>
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
