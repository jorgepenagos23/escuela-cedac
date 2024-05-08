<template>
    <v-container>
        <v-row dense>
            <v-col cols="6">
                <v-card
                color="indigo"
                width="400"
                title="Selecciona una de Inicio">
                    <v-date-picker
                    color="primary"
                    v-model="fechaInicio"
                    ></v-date-picker>

                </v-card>

            </v-col>

            <v-col cols="6">
                <v-card
                color="yellow"
                width="400"
                title="Selecciona una fecha de Fin"
                >
                <v-date-picker
                v-model="fechaFin"
                ></v-date-picker>
                </v-card>

            </v-col>
            <v-btn
            color="red"
            @click="enviarFecha"
            prepend-icon=""
            > Enviar Consulta por Rango</v-btn>

        </v-row>

        <v-card
        v-if="ProductoMasVendidoRango.length>0"
        >
        <v-col
        v-for="(producto , index) in  ProductoMasVendidoRango"
        :key="index"
        >
        <v-card
        color="lime-lighten-5
        "
        :title="producto.nombre_producto">
        <div class="text-green-darken-3 text-h6 font-weight-bold">Ventas: {{ producto.TotalPagado.toLocaleString() }} MXN </div>
        <div>Cantidad Comprada: {{ producto.CantidadComprada }}</div>


        </v-card>



        </v-col>


        </v-card>



    </v-container>


</template>

<script>
export default {

    data(){
        return{
            fechaInicio:null,
            fechaFin:null,
            ProductoMasVendidoRango:[]

        }

    },

    created(){

    },


    components:{

    },

    methods:{


        enviarFecha(){
              let  fechaInicio = this.fechaInicio;
             let   fechaFin =this.fechaFin;
            console.log('Fecha Inicio',fechaInicio)
            console.log('Fecha Final',fechaFin)


            axios.post('api/v1/productos/fechaconsulta', {

                    fechaInicio :this.fechaInicio,
                    fechaFin :this.fechaFin

            })
            .then(response=>{

                this.ProductoMasVendidoRango = response.data.ProductoMasVendidoRango;
                console.log('Envio exitoso', response)

            })

            .catch(function (error) {
                console.log('Error',error)
            })
        },

        recibirDatos(){


        }


    }


}
</script>

<style>

</style>
