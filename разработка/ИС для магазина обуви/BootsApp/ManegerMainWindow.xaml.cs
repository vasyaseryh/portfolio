using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.IO;

namespace BootsApp
{

    public partial class ManegerMainWindow : Window
    {
        public bootsEntities2 _context;
        public ManegerMainWindow()
        {
            InitializeComponent();
            _context = bootsEntities2.GetContext();
            DgTovar.ItemsSource = _context.Tovars.ToList();
       
        }

        private void GoHomeButt(object sender, RoutedEventArgs e)
        {
            new MainWindow().Show();
            this.Close();
        }

        private void TextBox_TextChanged(object sender, TextChangedEventArgs e)
        {
            DgTovar.ItemsSource = _context.Tovars.ToList().Where(x =>
                x.Наименование_товара.ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.Единица_измерения.ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.Поставщик.ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.Производитель.ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.Категория_товара.ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.Описание_товара.ToLower().Contains(SearchBox.Text.ToLower()) ||
                x.Фото.ToLower().Contains(SearchBox.Text.ToLower())
            ).ToList();
        }

        private void OrderButt(object sender, RoutedEventArgs e)
        {
            OrderMenegerWindow ow = new OrderMenegerWindow();
            ow.Show();
            this.Close();
        }
    }
}
