<template>
    <v-container>
        <v-col
        v-for="(producto, index) in ProductoMasVendido"
        :key="index"

        >
        <v-card>
            <v-card-title>{{ producto.nombre_producto }}</v-card-title>
            <v-card-text>
              <div>ID: {{ producto.id_Producto }}</div>
              <div class="text-green-darken-3 text-h6 font-weight-bold">Ventas: {{ producto.TotalPagado.toLocaleString() }} MXN </div>

              <div>Cantidad Comprada: {{ producto.CantidadComprada }}</div>

            </v-card-text>
          </v-card>
        </v-col>

      </v-container>



</template>

<script>
export default {

    data(){
        return{

            ProductoMasVendido:[]
        }

    },

    created(){

        this.obtenerDatos();
    },

  methods:{

    obtenerDatos() {
  axios.get('/api/v1/3productos/masvendidos')
    .then((response) => {
      this.ProductoMasVendido = response.data.ProductoMasVendido;
      console.log(this.ProductoMasVendido);
    })
    .catch((error) => {
      console.log('Error al Obtenerme', error);
    });
}

  }
};
</script>

<style></style>
