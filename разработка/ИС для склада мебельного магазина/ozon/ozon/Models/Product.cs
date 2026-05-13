using System;
using System.Collections.Generic;
using System.ComponentModel.DataAnnotations.Schema;
using System.Linq;
using System.Text;
using System.Threading.Tasks;


namespace ozon.Models
{
    public class Product
    {
        public int Id { get; set; }           // Первичный ключ
        public string Name { get; set; }      // Имя товара
        public string Description { get; set; }// Описание товара
        public string ImgUrl { get; set; }    // URL изображения
        public int Quantity { get; set; }
        public int Price { get; set; }
        public int lehgth { get; set; }
        public int width { get; set; }
        public int height { get; set; }

        [NotMapped] // Это атрибут Entity Framework, чтобы не сохранять в БД
        public string Dimensions { get; set; }

    }
}
